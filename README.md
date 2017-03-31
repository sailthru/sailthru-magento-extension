# Sailthru Magento Extension
### Version 2.0.0-RC1

The Magento extension provides Sailthru's base functionality and can serve as a framework for building custom Magento functionality. 

##### Features
- Optionally load Sailthru JavaScript (either Horizon or PersonalizeJS) to all pages
- Deliver all Magento transactionals through Sailthru for enhanced deliverability and tracking
- Record all orders submitted with campaign attribution and adjustment reporting
- Deliver abandoned cart emails.
- Add all users into the Sailthru system on login.
- Hook Magento's newsletter subscriptions into a Sailthru list.

##### Documentation
You can find documentation for this extension as well as in-depth documentation of our API on [Sailthru GetStarted](https://getstarted.sailthru.com/integrations/magento/magento-extension/).

##### Support
For questions or troubleshooting, please visit [Sailthru Support](https://sailthru.zendesk.com/hc/en-us) or submit an issue.

##### Known Bugs
* PersonalizeJS causes an issue with the default theme configurable swatch-picker. Our JS team is currently investigating.
* Error-handling during Sailthru service outages (rare) causes checkouts not to appear completed.

## Installation 
*Note: After installation, you will need to login, clear cache, and then re-login.*

### Magento Connect
You can [download the plugin from Magento Connect.](https://www.magentocommerce.com/magento-connect/sailthru-cross-channel-data-personalization-and-predictions.html)

### Manual Installation

#### With Modman
This extension can be used with [modman](https://github.com/colinmollenhour/modman), which
 preserves separation of the plugin from the rest of your Magento codebase.


```bash
cd <pathToSailthruExtension>
git clone git://github.com/sailthru/sailthru-magento-extension.git 
cd <pathToMagentoRoot>
modman init
modman link <pathToSailthruExtension>
```
or 
```bash
cd <pathToMagentoRoot>
modman init
modman clone git://github.com/sailthru/sailthru-magento-extension.git 
modman update sailthru-magento-extension # to update
```

#### Without Modman
If you don't want to use modman:
1. Download the plugin
    ```
    cd /tmp
    mkdir sailthru-magento
    cd sailthru-magento
    wget -O sailthru-magento.tar.gz https://github.com/sailthru/sailthru-magento-extension/tarball/master
    tar -zxvf sailthru-magento.tar.gz --strip-components=1 --show-transformed-names
    ```

2. Move into target directories
    ```
    export MAGENTO_BASE=<YOUR MAGENTO ROOT DIRECTORY>
    rsync -a app/ $MAGENTO_BASE/app/ --ignore-existing --whole-file
    ```
    


