# Promo

Promo is a promotion code discount system for the silverorange
[Store](https://github.com/silverorange/store) e-commerce platform.

Promo is responsible for the following object types and related tables:

- Promotion
- PromotionCode

It provides admin tools for managing promotions and codes. Additionally some
Store objects are extended to provide promotion code support:

- Order
- CartEntry

## Installation

Make sure the silverorange composer repository is added to the `composer.json`
for the project and then run:

```sh
composer require silverorange/promo
```
