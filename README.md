# ![](screenshots/icon.png) Nextcloud Draw.io / Diagrams.net integration app

This app allows users to create and edit diagrams in [Nextcloud](https://nextcloud.com) using [Draw.io](https://app.diagrams.net) / Diagrams.net on-line editor.

App Store link: https://apps.nextcloud.com/apps/drawio

Once installed, you will see an option to create a Draw.io diagram/whiteboard from the 'create file' menu.  Note: this app does not produce an app icon.

![](screenshots/drawio_add.png)

![](screenshots/drawio_integration.png)


## Info ##
- Requires [Nextcloud](https://nextcloud.com) >=20.0.0
- Version 20.8.0+ of diagrams.net (draw.io) is recommended.
- Real-time collaboration only works with the official online version of draw.io (https://embed.diagrams.net)
- Multi language support (l10n)
- Inspired by the old Draw.io Integration and OnlyOffice
- Tested with Chrome 58-96 and Firefox 53-89
- Tested with PHP 5.6/7.1/7.3/8.0/8.1
- Draw.io Integration tested with NextCloud 20.0.0 / 21.0.0 / 22.0.0 / 23.0.0 / 24.0.1 / v25.0.1
  
## Download ##

[Our Github releases page](https://github.com/jgraph/drawio-nextcloud/releases)

## Changelog ##

[Changelog](https://github.com/jgraph/drawio-nextcloud/blob/release/drawio/CHANGELOG.md)

## Installation ##
1. Copy Nextcloud draw.io integration app ("drawio" directory) to your Nextcloud server into the /apps/ directory
2. Go to "Apps" > "+ Apps" > "Not Enabled" and _Enable_ the **Draw.io** application
3. Go to "Admin settings > Draw.io" ( /index.php/settings/admin/drawio ) and click the "Save" button to register MIME types.


## Known Issues ##
- If you're experiencing problems while updating your Nextcloud intance, try to disable/delete Draw.io integration app (/apps/drawio/) and then install/copy it again after the NC update is completed.


## Configuration ##
Go to Admin page and change the settings you want:

![](screenshots/drawio_admin.png)

Click "Save" when you're done.

If you would like to self-host Draw.io, you might want to consider https://github.com/jgraph/docker-drawio (version 20.8.0+ recommended).


## License ##
- Released under the Affero General Public License version 3 or later.

## Contributors ##

[Contributors](https://github.com/jgraph/drawio-nextcloud/graphs/contributors)
