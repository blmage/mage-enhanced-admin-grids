# Enhanced Admin Grids


Enhanced Admin Grids extension for Magento.
More informations about the extension can be found on its Magento Connect page here : https://www.magentocommerce.com/magento-connect/enhanced-admin-grids-editor.html

## About the 0.8.9 version
The 0.8.9 version contains all the latest developed features of the extension, and is currently only available on github. It is still in an unstable state, as some fixes or new features (probably, mostly new custom columns) can be added at any moment.
It will be officially released on the Magento Connect when it will have reached a sufficient stability in terms of code and features, and depending on the feedbacks it will get.
In the waiting, prefer using it on test environments, and only live when you have tested all the features you need before.

#### New features and changes :
* lots of fixes and code refactoring
* widespread in-grid editor
* new permissions system (much more options, appliable per grid and role)
* new custom columns system (allows developers to add new columns in a simple way, without needing any block rewrite, and providing some customization options)
* new custom columns on the products grid : categories, wishlists and carts stats (and most of the inventory fields upcoming)
* new custom columns on the orders grid : billing and shipping addresses fields, payment and shipment methods, colorized order status
* products grid specific features (attributes, custom columns) shared with the category products tab
* custom default parameters behaviours
* pre-defined options sources (with possibility to add much more)
* and more ...

## Roadmap (and contribution)
From now on, and for a matter of time, I'll mostly focus on adding new custom columns, that will be released each time once ready on the active branch (currently 0.8.9).

However, here are some features that I was initially planning to add (if you wish to contribute, don't hesitate to take some ideas in this list, and to contact me if you have any question - new custom columns, bug fixes and other new features also being more than welcome) :

* flexible export system with customization options (to allow adding more export formats and be able to customize them finely)
* to be completed