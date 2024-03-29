# Changelog

## [1.2.4] - 2024-03-26
### New features
- Add address phone number validation on payment selection
- Disable insurance API calls when insurance is not enabled
- Add delivery confirmation CTA on admin order view
- Make financing solutions sortable in admin
- Add product exclusion on financing and insurance solutions
- Redirect to custom error page when an error occurs during financing process

## [1.2.3] - 2024-01-31
### Bugfix
- Fix displaying CTA for cancel subscription process on admin panel order detail view.

## [1.2.2] - 2024-01-24
### Bugfix
- Use customer lastname for default value on "birthname" field for POST /subscription API call, instead of empty value.
- Add alert message on BO order detail when order is on payment accepted state but financing isn't accepted.
### Security
- Don't display API access token on API log file.

## [1.2.1] - 2024-01-10
### Bugfix
- Use "NC" default value on "modele" field for POST /subscription API call, instead of empty value.

## [1.2.0] - 2024-01-04
### New feature
- Add module configuration to associate API financing state to native PrestaShop order state for automatic switching.
- Add new order confirmation page for module.

## [1.1.0] - 2023-11-10
- Some minors updates and fixes. 

## [1.0.0] - 2023-10-26
- Delivery of version `1.0.0` of plugin prestashop 1.6, 1.7.
- Content : e-financing solutions split payment & long term credit.
