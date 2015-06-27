Current plugins code base is Roundcube 1.0.

NOTE:
This repository may not be up-to-date all the time. Please install and use [Plugin Manger](http://myroundcube.com/myroundcube-plugins/plugin-manager) to download offical releases.


## [MyRoundcube Plugins](https://myroundcube.com) ##
Centralized and simplified management, language translation and plugins updates. [MyRoundcube Plugins](https://myroundcube.com/myroundcube-plugins) are delivered through our mirror servers by one of our own plugins; [Plugin Manager](https://myroundcube.com/myroundcube-plugins/plugin-manager). Plugin Manager is the one responsible for bringing all the goodies together. It adds the possibility for users to enable/disable plugins (at the user-level) while helping administrators stay current with updates, up-to-date plugins, bug fixes, centralized downloads, documentation and more. Plugin Manager does not modify Roundcube core files nor it will install plugins automatically.


Don't miss out on joining us at [MyRoundcube's community forum](http://forum.myroundcube.com).


## [MyRoundcube Load Splitter](https://myroundcube.com/myroundcube-plugins/load_splitter-plugin/load-splitter-service) ##


You can use MyRoundcube Load Splitter Service to serve static files for your webmail users with load\_splitter. [load\_splitter plugin](https://myroundcube.com/myroundcube-plugins/load_splitter-plugin) allows administrators to configure Roundcube Webmail to split its load, delivering static files (.js, .css and images) from different/separate servers. It attempts to further reduce file stats and improve end-users browsing experience by limiting the amount of files the web server hosting Roundcube webmail will have to serve per user/request while taking advantage of browser’s parallel downloads and Max Connections.

We have multiple servers geographically distributed delivering static files via BGP Anycast. By implementing Anycast, we make sure your users are always served static files from the closest MyRoundcube servers in their route. Also, it makes it easier for us to scale the service up when necessary. If one of our servers is found to be overloaded with requests, we can simply deploy another one in that specific location that would allow it to take some proportion of the overloaded server's requests. Additionally, as no client configuration is required, this can be done relatively quickly in our side.

The service is provided completely free of charge. This is yet another BIG thanks to all of you who are active participants in supporting our work. Last but not least, with load\_splitter you can host your own static files instead of using Myroundcube Load Splitter Service servers if you prefer so.

Try it and you may find it amazingly useful, specially if your webmail is sitting in a limited pipe or a home deployment.

## About this Repository ##
NOTE: MyRoundcube is partially commercial. According to the terms of GPL this repository holds the part of [MyRoundcube Plugins](http://myroundcube.com/myroundcube-plugins) which is released under the terms of GPL. Please use [Plugin Manager](http://myroundcube.com/myroundcube-plugins/plugin-manager) to get the entire bundle including those parts NOT released as OPEN SOURCE (NOT FREE and NOT GPL licensed).

### Localization Contributions: ###
  * [Translation Tool](http://dev.myroundcube.com/translator)