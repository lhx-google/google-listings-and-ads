# Usage Tracking

_Google Listings & Ads_ implements usage tracking, based on the native [WooCommerce Usage Tracking](https://woocommerce.com/usage-tracking/), and is only enabled when WooCommerce Tracking is enabled.

When a store opts in to WooCommerce usage tracking and uses _Google Listings & Ads_, they will also be opted in to the tracking added by _Google Listings & Ads_.

## What is tracked

As in WooCommerce core, only non-sensitive data about how a store is set up and managed is tracked. We **do not track or store personal data** from your clients.

* Plugin version
* Settings
  * WordPress.com account connection status
  * Google Merchant center account connection status

<!-- TODO: add more tracking information -->

### Tracking events

All event names are prefixed by `wcadmin_gla_`.

* `add_paid_campaign_clicked` - "Add paid campaign" button is clicked.
  * `context`: indicate the place where the button is located.
  * `href`: indicate the destination where the users is directed to, e.g. `'/google/setup-ads'`.

* `ads_account_connect_button_click` - Clicking on the button to connect an existing Google Ads account.

* `ads_account_create_button_click` - Clicking on the button to create a new Google Ads account, after agreeing to the terms and conditions.

* `ads_set_up_billing_click` - "Set up billing" button for Google Ads account is clicked.
  * `context`: indicate the place where the button is located, e.g. `setup-ads`.
  * `link_id`: a unique ID for the button within the context, e.g. `set-up-billing`.
  * `href`: indicate the destination where the users is directed to.

* `bulk_edit_click` - Triggered when the product feed "bulk edit" functionality is being used
  * `context`: name of the table
  * `number_of_items`: edit how many items
  * `visibility_to`: `("sync_and_show" | "dont_sync_and_show")`

* `chart_tab_click` - Triggered when a chart tab is clicked
  * `report`: name of the report (e.g. `"reports-programs" | "reports-products"`)
  * `context`: metric key of the clicked tab (e.g. `"sales" | "conversions" | "clicks" | "impressions" | "spend"`).

* `datepicker_update` - Triggered when datepicker (date ranger picker) is updated
  * `report`: name of the report (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
  * `compare, period, before, after`: Values selected in [datepicker](https://woocommerce.github.io/woocommerce-admin/#/components/packages/date-range-filter-picker/README?id=props)

* `dashboard_edit_program_click` - Triggered when "continue" to edit program button is clicked
  * `programId`: program id
  * `url`: url (free or paid)

* `disconnected_accounts` - Accounts are disconnected from the Setting page
  * `context`: (`all-accounts`|`ads-account`) - indicate which accounts have been disconnected.

* `documentation_link_click` - When a documentation link is clicked.
  * `link_id`: link identifier
  * `context`: indicate which link is clicked
  * `href`: link's URL

* `edit_product_click` - Trigger when edit links are clicked from product feed table
  * `status`: `("approved" | "partially_approved" | "expiring" | "pending" | "disapproved" | "not_synced")`
  * `visibility`: `("sync_and_show" | "dont_sync_and_show")`

* `edit_product_issue_click` - Trigger when edit links are clicked from Issues to resolve table
  * `code`: issue code returned from Google
  * `issue`: issue description returned from Google

* `filter` - Triggered when changing products & variations filter
  * `report`: name of the report (e.g. `"reports-products"`)
  * `filter`: value of the filter (e.g. `"all" | "single-product" | "compare-products"`)
  * `variationFilter`: value of the variation filter (e.g. `undefined | "single-variation" | "compare-variations"`)

* `free_ad_credit_country_click` - Clicking on the link to view free ad credit value by country.
  * `context`: indicate which page the link is in.

* `free_campaign_edited` - Saving changes to the free campaign.

* `get_started_faq` - Clicking on getting started page faq item to collapse or expand it
  * `id`: (faq identifier)
  * `action`: (`expand`|`collapse`)

* `get_started_notice_link_click` - Clicking on a text link within the notice on the Get Started page
  * `link_id`: link identifier
  * `context`: indicate which link is clicked
  * `href`: link's URL

* `google_account_connect_button_click` - Clicking on the button to connect Google account.

* `google_ads_account_link_click` - Clicking on a Google Ads account text link.
  * `context`: indicate which page / module the link is in
  * `link_id`: a unique ID for the link within the page / module

* `google_mc_link_click` - Clicking on a Google Merchant Center link.
  * `context`: indicate which page / module the link is in
  * `href`: link's URL

* `help_click` - "Help" button is clicked.
  * `context`: indicate the place where the button is located, e.g. `setup-ads`.

* `launch_paid_campaign_button_click` - Triggered when the "Launch paid campaign" button is clicked to add a new paid campaign
  * `audience`: country code of the paid campaign audience country
  * `budget`: daily average cost of the paid campaign

* `mc_account_connect_button_click` - Clicking on the button to connect an existing Google Merchant Center account.

* `mc_account_create_button_click` - Clicking on the button to create a new Google Merchant Center account, after agreeing to the terms and conditions.

* `mc_account_reclaim_url_agreement_check` - Clicking on the checkbox to agree with the implications of reclaiming URL.
  * `checked`: indicate whether the checkbox is checked or unchecked.

* `mc_account_reclaim_url_button_click` - Clicking on the button to reclaim URL for a Google Merchant Center account.

* `mc_url_switch`
  * `action` property is `required`: the Merchant Center account has a different, claimed URL and needs to be changed
  * `action` property is `success`: the Merchant Center account has been changed from blank, updated from a different, unclaimed URL, or after user confirmation of a required change.

* `modal_closed` - A modal is closed
  * `context`: indicate which modal is closed
  * `action`: indicate the modal is closed by what action (e.g. `maybe-later`|`dismiss` | `create-another-campaign`)
    * `maybe-later` is used when the "Maybe later" button on the modal is clicked
    * `dismiss` is used when the modal is dismissed by clicking on "X" icon, overlay, or pressing ESC
    * `create-another-campaign` is used when the button "Create another campaign" is clicked
    * `create-paid-campaign` is used when the button "Create paid campaign" is clicked

* `modal_content_link_click` - Clicking on a text link within the modal content
  * `context`: indicate which link is clicked
  * `href`: link's URL

* `modal_open` - A modal is opend
  * `context`: indicate which modal is opened

* `pre_launch_checklist_complete` - Triggered when all checklist items are complete / checked.

* `setup_ads` - Triggered on events during ads setup and editing
  * `target`: button ID
  * `trigger`: action (e.g. `click`)

* `setup_ads_faq` - Clicking on faq items to collapse or expand it in the Setup Ads page
  * `id`: (faq identifier)
  * `action`: (`expand`|`collapse`)

* `setup_mc` - Setup Merchant Center
  * `target`: button ID
  * `trigger`: action (e.g. `click`)

* `setup_mc_faq` - Clicking on faq items to collapse or expand it in the Setup Merchant Center page
  * `id`: (faq identifier)
  * `action`: (`expand`|`collapse`)

* `gla_site_claim` event
  * `action` property is `overwrite_required`: the site URL is claimed by another Merchant Center account and overwrite confirmation is required
  * `action` property is `success`: URL has been successfully set or overwritten.
  * `action` property is `failure`:
    *  `details` property is `independent_account`: unable to execute site claim because the provided Merchant Center account is not a sub-account of our MCA
    * `details` property is `google_proxy`: claim failed using the user creds (in the `Merchant` class)
    * `details` property is `google_manager`: claimed failed using MCA creds (paradoxically in the `Proxy` class)

* `site_verify_failure` - When a site verification with Google fails
  * `step` : the step of the process that failed (token, meta-tag, unknown)

* `site_verify_success` - When a site is successfully verified with Google

* `table_go_to_page` - When table pagination is changed by entering page via "Go to page" input
  * `context`: name of the table
  * `page`: page number (starting at 1)

* `table_header_toggle` - Toggling display of table columns
  * `report`: name of the report table (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
  * `column`: name of the column
  * `status`: (`on`|`off`)

* `table_page_click` - When table pagination is clicked
  * `context`: name of the table
  * `direction`: direction of page to be changed. `("next" | "previous")`

* `table_sort` - Sorting table
  * `report`: name of the report table (e.g. `"dashboard" | "reports-programs" | "reports-products" | "product-feed"`)
  * `column`: name of the column
  * `direction`: (`asc`|`desc`)

* `tooltip_viewed` - Viewing tooltip
  * `id`: (tooltip identifier)

* `wordpress_account_connect_button_click` - Clicking on the button to connect WordPress.com account.

<!-- -- >
## Developer Info
All new tracking info should be updated in this readme.

New snapshot data for **WC Tracker** should be hooked into `Tracking\Events\TrackerSnapshot::include_snapshot_data()`.

New **Tracks** events should be created in `Tracking\Events\Events` (extending `Tracking\Events\BaseEvent`), and need to be registered in `Tracking\Events\EventTracking::$events`. They should also be registered in the `Internal\DependencyManagement\CoreServiceProvider` class:

```php
$this->conditionally_share_with_tags( Loaded::class );
```

 /Dev Info -->
