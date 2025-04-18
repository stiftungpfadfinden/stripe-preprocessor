<?php

require_once 'meinestiftung.civix.php';

// phpcs:disable
use CRM_Meinestiftung_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function meinestiftung_civicrm_config(&$config): void {
  _meinestiftung_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function meinestiftung_civicrm_install(): void {
  _meinestiftung_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function meinestiftung_civicrm_postInstall(): void {
  _meinestiftung_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function meinestiftung_civicrm_uninstall(): void {
  _meinestiftung_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function meinestiftung_civicrm_enable(): void {
  _meinestiftung_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function meinestiftung_civicrm_disable(): void {
  _meinestiftung_civix_civicrm_disable();
}

function meinestiftung_civicrm_alterPaymentProcessorParams($object, $propertyBag, array &$checkoutSessionParams) {
  // Log payment properties for testing
  CRM_Core_Error::debug_log_message(print_r($propertyBag,TRUE));
  CRM_Core_Error::debug_log_message(print_r($checkoutSessionParams,TRUE));
  
  // Let stripe decide which payment types to use
  unset($checkoutSessionParams["payment_method_types"]);

  // Get data from payment properties for events and contributions
  $first_name = $propertyBag->has('firstName') ? $propertyBag->getFirstName() : '';
  $last_name = $propertyBag->has('lastName') ? $propertyBag->getLastName() : '';
  $email = $propertyBag->getEmail;
  $unit_amount = $checkoutSessionParams['line_items'][0]['price_data']['unit_amount'];
   
  // Is this a payment for an event registration or a contribution?
  // Don't change Stripe data for any other kinds of payment
  if($propertyBag->has('eventID')) {
    // Event payment
    // Get event name
    $item_name = $propertyBag->has('item_name') ? $propertyBag->getCustomProperty('item_name') : '';
    // Shorten text in item_name
    $item_name = str_replace("Online-Veranstaltungsanmeldung", "Teilnahme", $item_name);
    // Put name of participant and event name into description
    if (empty($first_name) and empty($last_name)) {
      $description = $item_name;
    } else {
      $description = "{$item_name} von {$first_name} {$last_name}";
    }
  } elseif ($propertyBag->has('contributionType_name')) {
    // Contribution
    $contribution_type = $propertyBag->has('contributionType_name') ? $propertyBag->getCustomProperty('contributionType_name') : '';
    $contribution_source = $propertyBag->has('contribution_source') ? $propertyBag->getCustomProperty('contribution_source') : '';
    if (empty($first_name) and empty($last_name)) {
      $description = $contribution_type;
    } else {
      $description = "{$contribution_type} von {$first_name} {$last_name}";
    }
    // Set Stripe line item for invoicing
    $checkoutSessionParams['line_items'][0]['price_data']['product_data']['name'] = $contribution_type;
    // If quantity is > 1 (happens with CiviCRM pricing tables), set it to 1 and adjust unit amount
    $quantity = $checkoutSessionParams['line_items'][0]['quantity'];
    if ($quantity != 1) {
      $checkoutSessionParams['line_items'][0]['quantity'] = 1;
      $checkoutSessionParams['line_items'][0]['price_data']['unit_amount'] = $quantity * $unit_amount;
    }
    // Contribution Source
    if (empty($contribution_source)) {
      unset($checkoutSessionParams['line_items'][0]['price_data']['product_data']['description']);
    } else { 
      $checkoutSessionParams['line_items'][0]['price_data']['product_data']['description'] = $contribution_source;
    }
  } else {
    // Other payment type: use standard description from CiviCRM
    $description = $checkoutSessionParams['payment_intent_data']['description'];
  }

  // Set Stripe description which will be shown in the Stripe portal
  if ($checkoutSessionParams['mode'] == 'payment') {
    // One-time payment
    $checkoutSessionParams['payment_intent_data']['description'] = $description;
  } elseif ($checkoutSessionParams['mode'] == 'subscription') {
    // Subscription
    $checkoutSessionParams['subscription_data']['description'] = $description;
  }

  CRM_Core_Error::debug_log_message(print_r($checkoutSessionParams,TRUE));
}