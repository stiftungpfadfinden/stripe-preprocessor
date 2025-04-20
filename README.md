# Stripe Preprocessor
* This extension enables you to process and change CiviCRM contribution/event data before it is sent to Stripe via the [Stripe extension]. 
* You can also use it to disable sending choices for payment types like Credit Card, SEPA or PayPal to Stripe and instead configure your
payment types within Stripe only. This is very useful if you want to use PayPal via Stripe, because PayPal can't be selected as a payment intent in the 
[Stripe extension].

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).

Say you are using Stripe with your CiviCRM installation to process payments for contributions and events. You've set up everything with the excellent
[Stripe extension], but you don't really like the way your payment data is showing up in your Stripe transaction reports.

### Contributions
If you set up a standard contribution page in CiviCRM that uses multiple price levels your users can choose from, a contribution shows up in Stripe like this:
* Description (the line in the transaction log): `Online-Zuwendung: <Contribution page name> 1234567 #01234567890abcdef01234567890abcdef`. To find out the name of the person that contributed, you need to click onto every Stripe transaction because the name isn't in the description line.
* Stripe sets up an (in this case) unnecessary "product" called "Contribution level" or whatever you called the price levels of your contributions.

### Events
If you set up an event with different price levels, the payment shows up in Strip something like
* Description: Online-Veranstaltungsanmeldung: `1234567 #01234567890abcdef01234567890abcdef`. You can't see the name of the person who signed up in the description.
* Items people chose for the event are shown as "Please select an item" etc. instead of just the item name.

## What this extension does
This extension uses the [Stripe extension] hook `alterPaymentProcessorParams` to change the data that is sent to Stripe when a payment is processed.
This hook is called after the user has clicked "Contribute" etc. in CiviCRM. Since it's hard to find documentation on that function on the web, you can
use this extension as a boilerplate to create your own extension for your organization.
* This extension does not have a configuration screen -- you need to adapt the PHP code in your own version of it.
* We are using this in production with the Stripe Checkout screen only (that's when you type in your payment data on a separate page after hitting the "Contribute" button in CiviCRM).

## How to change data before it gets sent to Stripe
### Test, test, test
* First of all, you need to be able to test Stripe without making actual payments. Set up a [developer sandbox] in Stripe for this purpose and 
configure the [Stripe extension] with the API keys from the sandbox so that the basic payment process with Stripe works. You've probably done this already
when you set up your Stripe workflow in the first place.

### Data the extension receives
When the user hits 'Contribute' (or another payment button in CiviCRM), this extension receives two PHP entities:

#### $propertyBag
* This object contains basically all data the user has entered for a contribution, event sign-up etc. It is read-only for our purposes and used by the
[Stripe extension] to send the necessary data to Stripe.

#### $checkoutSessionParams
* This array contains data to be sent to Stripe. We can change the data (like line items and the description) to whatever we would like Stripe to receive.
* It also contains the array `payment_intent_data` with the payment intents that have been selected in the [Stripe extension] setup.

### Changing data before it is sent to Stripe
* The topmost PHP function in the script `stripe_preprocessor.php` is called when a payment is made. Please refer to the comments in this script to
see how you can get data from `$propertyBag` use it to change data in `$checkoutSessionParams` before it gets sent to Stripe.

[Stripe extension]: https://lab.civicrm.org/extensions/stripe
[developer sandbox]: https://docs.stripe.com/sandboxes
