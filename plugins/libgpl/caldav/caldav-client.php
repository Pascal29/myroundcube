<?php

/**
 * CalDAV Client
 *
 * @version @package_version@
 * @author Daniel Morlock <daniel.morlock@awesome-it.de>
 *
 * Copyright (C) 2013, Awesome IT GbR <info@awesome-it.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require_once (dirname(__FILE__).'/../../libgpl/SabreDAV/vendor/autoload.php');
require_once (dirname(__FILE__).'/vobject_sanitize.php');


class caldav_client extends Sabre\DAV\Client
{
    const CLARK_GETCTAG = '{http://calendarserver.org/ns/}getctag';
    const CLARK_GETETAG = '{DAV:}getetag';
    const CLARK_CALDATA = '{urn:ietf:params:xml:ns:caldav}calendar-data';

    public $base_uri;
    public $path;
    private $libvcal;
    private $rc;
    private $user_agent;

    /**
     *  Default constructor for CalDAV client.
     *
     * @param string Caldav URI to appropriate calendar.
     * @param string Username for HTTP basic auth.
     * @param string Password for HTTP basic auth.
     * @param boolean Verify SSL cert. // Mod by Rosali (https://gitlab.awesome-it.de/kolab/roundcube-plugins/issues/1)
     */
    public function __construct($uri, $user = null, $pass = null, $authtype = 'detect', $verifySSL = array(true, true)) // Mod by Rosali (https://gitlab.awesome-it.de/kolab/roundcube-plugins/issues/1)
    {
        $this->user_agent = 'MyRoundcube-SabreDAV/' . Sabre\DAV\Version::VERSION;

        // Include libvcalendar on demand ...
        if(!class_exists("libvcalendar"))
            require_once (INSTALL_PATH . 'plugins/libgpl/libcalendaring/libvcalendar.php');

        $this->libvcal = new libvcalendar();
        $this->rc = rcube::get_instance();

        $tokens = parse_url($uri);
        $this->base_uri = $tokens['scheme']."://".$tokens['host'].($tokens['port'] ? ":".$tokens['port'] : null);
        $this->path = $tokens['path'].($tokens['query'] ? "?".$tokens['query'] : null);

        switch($authtype){
          case 'digest':
            $auth = Sabre\DAV\Client::AUTH_DIGEST;
            break;
          case 'basic':
            $auth = Sabre\DAV\Client::AUTH_BASIC;
            break;
          default:
            $auth = Sabre\DAV\Client::AUTH_BASIC | Sabre\DAV\Client::AUTH_DIGEST;
        }

        $settings = array(
            'baseUri' => $this->base_uri,
            'authType' => $auth
        );

        if ($user) $settings['userName'] = $user;
        if ($pass) $settings['password'] = $pass;

        parent::__construct($settings);

        $this->verifyPeer = $verifySSL[0];
        $this->verifyHost = $verifySSL[1];
        
    }

    /**
     * Fetches calendar ctag.
     *
     * @see http://code.google.com/p/sabredav/wiki/BuildingACalDAVClient#Retrieving_calendar_information
     * @return Calendar ctag or null on error.
     */
    public function get_ctag()
    {
        try
        {
            $arr = $this->propFind($this->path, array(self::CLARK_GETCTAG));

            if (isset($arr[self::CLARK_GETCTAG]))
                return $arr[self::CLARK_GETCTAG];
        }
        catch(Sabre\DAV\Exception $err)
        {
            rcube::raise_error(array(
                'code' => $err->getHTTPCode(),
                'type' => 'DAV',
                'file' => $err->getFile(),
                'line' => $err->getLine(),
                'message' => $err->getMessage()
            ), true, false);
        }

        return null;
    }

    /**
     * Fetches event etags and urls.
     *
     * @see http://code.google.com/p/sabredav/wiki/BuildingACalDAVClient#Finding_out_if_anything_changed
     *
     * @param array Optional list of relative event URL's to retrieve specific etags. If not specified, all etags of the current calendar are returned.
     * @return array List of etag properties with keys:
     *    url: Event ical path relative to the calendar URL.
     *   etag: Current event etag.
     */
    public function get_etags(array $event_urls = array())
    {
        $etags = array();

        try
        {
            $arr = $this->prop_report($this->path, array(self::CLARK_GETETAG), $event_urls);
            foreach ($arr as $path => $data)
            {
                // Some caldav server return an empty calendar as event where etag is missing. Skip this!
                if($data[self::CLARK_GETETAG])
                {
                    array_push($etags, array(
                       "url" => $path,
                       "etag" => str_replace('"', null, $data[self::CLARK_GETETAG])
                    ));
                }
            }
        }
        catch(Sabre\DAV\Exception $err)
        {
            rcube::raise_error(array(
                'code' => $err->getHTTPCode(),
                'type' => 'DAV',
                'file' => $err->getFile(),
                'line' => $err->getLine(),
                'message' => $err->getMessage()
            ), true, false);
        }

        return $etags;
    }

    /**
     * Fetches calendar events.
     *
     * @see http://code.google.com/p/sabredav/wiki/BuildingACalDAVClient#Downloading_objects
     * @param array $urls = array() Optional list of event URL's to fetch. If non is specified, all
     *        events from the appropriate calendar will be fetched.
     * @return Array hash list that maps the events URL to the appropriate event properties.
     */
    public function get_events($urls = array())
    {
        $events = array();

        try
        {
            $vcals = $this->prop_report($this->path, array(
                self::CLARK_GETETAG,
                self::CLARK_CALDATA
            ), $urls);
            
            foreach ($vcals as $path => $response)
            {
                $vcal = $response[self::CLARK_CALDATA];
                $vobject_sanitize = new vobject_sanitize($vcal, array('CATEGORIES'), 'serialize');
                $vcal = $vobject_sanitize->vobject;
                $vobject_sanitize = new vobject_sanitize($vcal, array('RDATE'), 'unserialize');
                $vcal = $vobject_sanitize->vobject;
                foreach ($this->libvcal->import($vcal) as $event) {
                    $events[$path] = $event;
                }
            }
        }
        catch(Sabre\DAV\Exception $err)
        {
            rcube::raise_error(array(
                'code' => $err->getHTTPCode(),
                'type' => 'DAV',
                'file' => $err->getFile(),
                'line' => $err->getLine(),
                'message' => $err->getMessage()
            ), true, false);
        }
        return $events;
    }

    /**
     * Does a REPORT request
     *
     * @param string $url
     * @param array $properties List of requested properties must be specified as an array, in clark
     *        notation.
     * @param array $event_urls If specified, a multiget report request will be initiated with the
     *        specified event urls.
     * @param int $depth = 1 Depth should be either 0 or 1. A depth of 1 will cause a request to be
     *        made to the server to also return all child resources.
     * @return array Hash with ics event path as key and a hash array with properties and appropriate values.
     */
    public function prop_report($url, array $properties, array $event_urls = array(), $depth = 1)
    {
        $url = slashify($url); // iCloud
        
        $parent_tag = sizeof($event_urls) > 0 ? "c:calendar-multiget" : "d:propfind";
        $method = sizeof($event_urls) > 0 ? 'REPORT' : 'PROPFIND';

        $body = '<?xml version="1.0"?>'."\n".'<'.$parent_tag.' xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">'."\n";

        $body .= '  <d:prop>'."\n";
        foreach ($properties as $property)
        {

            list($namespace, $elementName) = Sabre\DAV\XMLUtil::parseClarkNotation($property);

            if ($namespace === 'DAV:')
            {
                $body .= '    <d:'.$elementName.' />'."\n";
            }
            else
            {
                $body .= '    <x:'.$elementName.' xmlns:x="'.$namespace.'"/>'."\n";
            }
        }
        $body .= '  </d:prop>'."\n";

        // http://tools.ietf.org/html/rfc4791#page-90
        // http://www.bedework.org/trac/bedework/wiki/Bedework/DevDocs/Filters
        /*
         if($start && $end)
         {
         $body.= '  <c:filter>'."\n".
         '    <c:comp-filter name="VCALENDAR">'."\n".
         '      <c:comp-filter name="VEVENT">'."\n".
         '        <c:time-range start="'.$start.'" end="'.$end.'" />'."\n".
         '      </c:comp-filter>'."\n".
         '    </c:comp-filter>'."\n".
         '  </c:filter>' . "\n";
         }
         */

        foreach ($event_urls as $event_url)
        {
            $body .= '<d:href>'.$event_url.'</d:href>'."\n";
        }

        $body .= '</'.$parent_tag.'>';

        $response = $this->request($method, $url, $body, array(
            'Depth' => $depth,
            'Content-Type' => 'application/xml',
            'User-Agent' => $this->user_agent
        ));

        $result = $this->parseMultiStatus($response['body']);

        // If depth was 0, we only return the top item
        if ($depth === 0)
        {
            reset($result);
            $result = current($result);
            return isset($result[200]) ? $result[200] : array();
        }

        $new_result = array();
        foreach ($result as $href => $status_list)
        {
            $new_result[$href] = isset($status_list[200]) ? $status_list[200] : array();
        }

        return $new_result;
    }

    /**
     * Updates or creates a calendar event.
     *
     * @see http://code.google.com/p/sabredav/wiki/BuildingACalDAVClient#Updating_a_calendar_object
     * @param string Event ics path for the event.
     * @param array Hash array with event properties.
     * @param string Current event etag to match against server data. Pass null for new events.
     * @return True on success, -1 if precondition failed i.e. local etag is not up to date, false on error.
     */
    public function put_event($path, $event, $etag = null)
    {
        try
        {
            $headers = array(
              'Content-Type' => 'text/calendar; charset=utf-8',
              'User-Agent' => $this->user_agent
            );
            if ($etag) $headers["If-Match"] = '"'.$etag.'"';

            // Temporarily disable error reporting since libvcal seems not checking array key properly.
            // TODO: Remove this todo if we could ensure that those errors come not from incomplete event properties.
            //$err_rep = error_reporting(E_ERROR);
            $vcal = $this->libvcal->export(array($event));
            if (is_array($vcal))
                $vcal = array_shift($vcal);

            //error_reporting($err_rep);
            $response = $this->request('PUT', $path, $vcal, $headers);

            // Following http://code.google.com/p/sabredav/wiki/BuildingACalDAVClient#Creating_a_calendar_object, the
            // caldav server must not always return the new etag.

            return $response["statusCode"] == 201 || // 201 (created, successfully created)
                   $response["statusCode"] == 204;   // 204 (no content, successfully updated)
        }
        catch(Sabre\DAV\Exception\PreconditionFailed $err)
        {
            // Event tag not up to date, must be updated first ...
            return -1;
        }
        catch(Sabre\DAV\Exception $err)
        {
            rcube::raise_error(array(
                'code' => $err->getHTTPCode(),
                'type' => 'DAV',
                'file' => $err->getFile(),
                'line' => $err->getLine(),
                'message' => $err->getMessage()
            ), true, false);
        }
        return false;
    }

    /**
     * Removes event of given URL.
     *
     * @see http://code.google.com/p/sabredav/wiki/BuildingACalDAVClient#Deleting_a_calendar_object
     * @param string Event ics path for the event.
     * @param string Current event etag to match against server data. Pass null to force removing the event.
     * @return True on success, -1 if precondition failed i.e. local etag is not up to date, false on error.
     **/
    public function remove_event($path, $etag = null)
    {
        return $this->delete_request($path, $etag);
    }
    
    /**
     * Fires a DELETE request to a given URL.
     *
     * @see http://code.google.com/p/sabredav/wiki/BuildingACalDAVClient#Deleting_a_calendar_object
     * @param string Path.
     * @param string Current etag to match against server data or null.
     * @return True on success, -1 if precondition failed i.e. local etag is not up to date, false on error.
     **/
    public function delete_request($path, $etag = null)
    {
        try
        {
            $headers = array(
              'Content-Type' => 'text/calendar; charset=utf-8',
              'User-Agent' => $this->user_agent
            );
            if ($etag) $headers["If-Match"] = '"'.$etag.'"';

            $response = $this->request('DELETE', $path, null, $headers);
            return $response["statusCode"] == 204;   // 204 (no content, successfully deleted);
        }
        catch(Sabre\DAV\Exception\PreconditionFailed $err)
        {
            // Event tag not up to date, must be updated first ...
            return -1;
        }
        catch(Sabre\DAV\Exception $err)
        {
            rcube::raise_error(array(
                'code' => $err->getHTTPCode(),
                'type' => 'DAV',
                'file' => $err->getFile(),
                'line' => $err->getLine(),
                'message' => $err->getMessage()
            ), true, false);
        }
        return false;
    }

    /**
     * Make a propFind query to caldav server
     * @param string $path absolute or relative URL to Resource
     * @param array $props list of properties to use for the query. Properties must have clark-notation.
     * @param int $depth 0 means no recurse while 1 means recurse
     * @param boolean $log log exception
     * @return array
     */
    public function prop_find($path, $props, $depth, $log = true)
    {
        try {
            $response = $this->propFind($path, $props, $depth);
        }
        catch(Sabre\DAV\Exception $err)
        {
            rcube::raise_error(array(
                'code' => $err->getHTTPCode(),
                'type' => 'DAV',
                'file' => $err->getFile(),
                'line' => $err->getLine(),
                'message' => $err->getMessage()
            ), $log, false);
        }
        return $response;
    }
    
    /**
     * Add a caldendar collection
     * @param string collection URL
     * @parma string displayname
     * @param string resource type
     * @param string namespace
     * @return array response
     */
    public function add_collection($url, $displayname, $resourcetype, $namespace)
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>' .
                '<D:mkcol xmlns:D="DAV:"xmlns:C="urn:ietf:params:xml:ns:' . $namespace . '">' .
                '  <D:set>' .
                '    <D:prop>' .
                '      <D:resourcetype>' .
                '      <D:collection/> ' .
                '        <C:' . $resourcetype . '/>' .
                '      </D:resourcetype>' .
                '      <D:displayname>' . $displayname . '</D:displayname>' .
                '    </D:prop>' .
                '    </D:set>' .
                '  </D:mkcol>';
        
        try {
            $response = $this->request('MKCOL', $url, $body, array(
                'Content-Type' => 'application/xml',
                'User-Agent' => $this->user_agent
            ));
        }
        catch(Sabre\DAV\Exception $err)
        {
            return false;
        }
        return $response;
    }
    
    /**
     * Freebusy request for a given user
     * @param string  username
     * @param path    relative path to base uri
     * @param integer unix timestamp
     * @param integer unix timestamp
     * @retrun array  List of busy timeslots within the requested range
     */
    public function freebusy($user, $path, $start, $end)
    {
        $body = '<?xml version="1.0" encoding="utf-8" ?>' .
                '<C:free-busy-query xmlns:C="urn:ietf:params:xml:ns:caldav">' .
                '<C:time-range start="' . gmdate("Ymd\THis\Z", $start) . '"' .
                '     end="' . gmdate("Ymd\THis\Z", $end) . '"/>' .
                '</C:free-busy-query>';
        return $this->request('REPORT', $path, $body, array(
            'Content-Type' => 'application/xml',
            'Depth' => 1,
            'User-Agent' => $this->user_agent
        ));
    }
};
?>
