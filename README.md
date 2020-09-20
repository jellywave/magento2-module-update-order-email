# Update order email CLI tool for Magento2 

A simple command line tool that allows for updating order email addresses.

`sales:order:update-email`

##Install
### Installation Using Composer (recommended)

`composer require jellywave/magento2-module-update-order-email`

`bin/magento setup:upgrade`

`bin/magento setup:di:compile`

## Parameters 
Global config parameters:

**-i|--increment_id**	Specify a order by increment ID to update

**-e|--email**  Specify a list of orders by email to update

## Known issues
Currently there is no validation on orders being moved between websites. 
The Store it not updated on orders when associated to a customer account on a different store.
 