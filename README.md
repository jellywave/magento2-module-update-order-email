# Update order email CLI tool for Magento2 

A simple command line tool that allows for updating order email addresses.

`sales:order:update-email`

##Install
### Installation Using Composer (recommended)

Add to compose.json

```           
    "require": {
      ...
      "jellywave/magento2-module-update-order-email":"1.0.0"
    },            
```

```
    "repositories": [
      ...
      {
        "type": "package",
        "package": {
          "name": "jellywave/magento2-module-update-order-email",
          "version": "1.0.0",
          "source": {
            "url": "https://github.com/jellywave/magento2-module-update-order-email",
            "type": "git",
            "reference": "master"
          }
        }
      }
      ...
    ]
```

`composer require jellywave/magento2-module-update-order-email`
This method will not work unless the repository have been set

`bin/magento setup:upgrade`

`bin/magento setup:di:compile`

## Parameters 
Global config parameters:

**-i|--increment_id**	Specify a order by increment ID to update

**-e|--email**  Specify a list of orders by email to update

## Known issues
Currently there is no validation on orders being moved between websites. 
The Store it not updated on orders when associated to a customer account on a different store.
 