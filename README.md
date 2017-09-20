# Sailthru Magento Extension
### Version 2.1.0 - Beta

The Magento extension provides an integration to the Sailthru platform and can serve as a framework for building custom Magento functionality. 

##### Features
- Automatically deploy Sailthru Javascript (Horizon or Sailthru Script Tag).
- Add all users into the Sailthru system on login or registration.
- Hook Magento's newsletter subscriptions into a Sailthru list.
- Deliver Magento transactionals through Sailthru for enhanced deliverability, tracking, and user flows.
- Create custom Sailthru templates for Customer Signup, Newsletter Signup, Order Confirmation, and Shipping.
- Capture customers order data with adjustments and Sailthru campaign attribution.
- Deliver abandoned cart emails for known users.

##### Beta Features
- **Send to Sailthru Mass Action**
    A new mass action that sends bulk product updates to Sailthru from the admin product catalog screen.
    *May run into memory issues.*

##### PrototypeJS 1.7 Issue
Sailthru Script Tag is compiled with Babel, which [causes issues with Magento's bundled PrototypeJS 1.7.0.][1] We've specifically found that the default frontend configurable-swatch picker is unable to select. The Babel issue appears to be resolved by updating Prototype to 1.7.3.

##### Documentation
Documentation is available for the extension and our APIs on the [Sailthru GetStarted][2] docs site.
* [Magento 1 Extension v2][3]
* [Sailthru APIs][4]

##### Support
For questions or troubleshooting, please visit [Sailthru Support][5] or submit an issue on GitHub.

##### Migrating from 1.x
* We've moved codepools. Before installing, make sure to remove the current plugin.
* The 2.0.0 uses updated schemas for User and Template Vars. Visit the documentation above to see the full schemas and what's changed.

## Installation 
* *After installation, you may need to login, clear cache, and then re-login.*
* *This repo defaults to the **develop** branch. If looking for latest LTS release, please use **master**.*

### Manual Installation

#### (Migration from 1.x only) Removing the old extension
Before installing the 2.x plugin, please remove the following files.
```
app/code/local/Sailthru/Email
app/design/adminhtml/default/default/template/sailthruemail
app/design/frontend/base/default/layout/sailthru.xml
app/design/frontend/base/default/template/sailthru
app/etc/modules/Sailthru_Email.xml
```

#### Installation with modman
This extension can be used with [modman][6], which preserves separation of the plugin from the rest of your Magento codebase.


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

#### Installation without modman
If you don't want to use modman:
1. Download the plugin
    ```
    cd /tmp
    mkdir sailthru-magento
    cd sailthru-magento
    curl -L https://github.com/sailthru/sailthru-magento-extension/tarball/<selected_branch> | tar zx --strip-components 1
    ```

2. Move into target directories
    ```
    cp -r app/* <magento_root>/app/
    ```
    
[1]: https://github.com/babel/babel/issues/5518
[2]: https://getstarted.sailthru.com/
[3]: https://getstarted.sailthru.com/integrations/magento/magento-1-extension-v2/
[4]: https://getstarted.sailthru.com/developers/api-basics/introduction/
[5]: https://sailthru.zendesk.com/hc/en-us
[6]: https://github.com/colinmollenhour/modman


