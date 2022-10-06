# ![](screenshots/icon.png) Nextcloud Draw.io / Diagrams.net integration app

This app allows users to create and edit diagrams in [Nextcloud](https://nextcloud.com) using [Draw.io](https://app.diagrams.net) / Diagrams.net on-line editor.

App Store link: https://apps.nextcloud.com/apps/drawio

Once installed, you will see an option to create a Draw.io diagram from the 'create file' menu.  Note: this app does not produce an app icon.

![](screenshots/drawio_add.png)

![](screenshots/drawio_integration.png)


## Info ##
- Requires [Nextcloud](https://nextcloud.com) >11.0.0
- Multi language support (l10n)
- Inspired by the old Draw.io Integration and OnlyOffice
- Tested with Chrome 58-96 and Firefox 53-89
- Tested with PHP 5.6/7.1/7.3/8.0/8.1
- Draw.io Integration v1.0.3 tested with NextCloud 11.0.3 / 12.0.2 / 13.0.6 / 14.0.4 / 15.0.0 / 16.0.0 / 17.0.0 / 18.0.0 / 19.0.0 / 20.0.0 / 21.0.0 / 22.0.0 / 23.0.0 / 24.0.1
  

## Mimetype detection ##

To make Draw.io work properly, you need to add a new mimetypes in the `mimetypemapping.json` file (at Nextcloud level).

Go to `Admin settings > Additional settings` ( `/index.php/settings/admin/additional` ) and click the `Save` button to register MIME types.

Or you can do it manually:
- Download [mimetypemapping.json](https://raw.githubusercontent.com/jgraph/drawio-nextcloud/dev/mimetypemapping.json) and save it in `config` folder
or 
- Copy `/resources/config/mimetypemapping.dist.json` to `/config/mimetypemapping.json` 
(in the `config/` folder at Nextcloud’s root directory; the file should be stored next to the `config.php` file). 
Afterwards add the two following line just after the “_comment” lines.
    "drawio": ["application/x-drawio"],

If all other mimetypes are not working properly, just run the
following command:

    sudo -u www-data php occ files:scan --all

## Changelog ##
[Changelog](https://github.com/jgraph/drawio-nextcloud/blob/dev/CHANGELOG.md)


## Installation ##
1. Copy Nextcloud draw.io integration app ("drawio" directory) to your Nextcloud server into the /apps/ directory
2. Go to "Apps" > "+ Apps" > "Not Enabled" and _Enable_ the **Draw.io** application
3. Go to "Admin settings > Additional settings" ( /index.php/settings/admin/additional ) and click the "Save" button to register MIME types.


## Known Issues ##
- If you're experiencing problems while updating your Nextcloud intance, try to disable/delete Draw.io integration app (/apps/drawio/) and then install/copy it again after the NC update is completed.


## Configuration ##
Go to Admin page and change the settings you want:

![](screenshots/drawio_admin.png)

Click "Save" when you're done.

If you would like to self-host Draw.io, you might want to consider https://github.com/jgraph/docker-drawio from the creators of Draw.io (now [diagrams.net](https://www.diagrams.net/)).


## License ##
- Released under the Affero General Public License version 3 or later.
- [CC 3.0 BY] File icon made by [DinosoftLabs](http://www.flaticon.com/authors/dinosoftlabs) / [Link](http://www.flaticon.com/free-icon/organization_348440)


## Contributors ##
- [pawelrojek](https://github.com/pawelrojek)
- [geiseri](https://github.com/geiseri)
- [arnowelzel](https://github.com/arnowelzel)
- [githubkoma](https://github.com/githubkoma)
- [schizophrene](https://github.com/schizophrene)
- [xlyz](https://github.com/xlyz)
- [cuthulino](https://github.com/cuthulino)
- [tavinus](https://github.com/tavinus)
- [LEDfan](https://github.com/LEDfan)
- [mario](https://github.com/mario)
- [ColdSphinX](https://github.com/ColdSphinX)
- [acidhunter](https://github.com/acidhunter)
- [janLo](https://github.com/janLo)
- [Irillit](https://github.com/Irillit/)
- [Luckyvb](https://github.com/Luckyvb)
- [teemue] (https://github.com/teemue)
- [p-bo] (https://github.com/p-bo)

[View all](https://github.com/jgraph/drawio-nextcloud/graphs/contributors)
