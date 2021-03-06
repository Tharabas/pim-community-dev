@javascript
Feature: Validate localized metric attributes of a product
  In order to keep my data consistent
  As a regular user
  I need to be able to see validation errors for metric attributes

  Background:
    Given the "default" catalog configuration
    And the following attributes:
      | code   | label-fr_FR | type   | scopable | localizable | metric_family | default_metric_unit | negative_allowed | decimals_allowed | number_min | number_max |
      | area   | Zone        | metric | no       | yes         | Area          | HECTARE             | no               | no               |            |            |
      | length | Taille      | metric | yes      | yes         | Length        | METER               | no               | no               |            |            |
      | power  | Puissance   | metric | no       | yes         | Power         | WATT                | yes              | yes              | -200       | -100       |
      | speed  | Vitesse     | metric | yes      | yes         | Speed         | YARD_PER_HOUR       | yes              | yes              | 5.50       | 100        |
    And the following family:
      | code | label-en_US | attributes                      |
      | baz  | Baz         | sku, area, length, power, speed |
    And the following product:
      | sku | family |
      | foo | baz    |
    And I am logged in as "Julien"
    And I am on the "foo" product page

  Scenario: Validate the decimals allowed constraint of metric attribute
    Given I change the Zone to "2,7 HECTARE"
    And I save the product
    Then I should see validation tooltip "Cette valeur ne doit pas être un nombre décimal."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the decimals allowed constraint of scopable metric attribute
    Given I switch the scope to "ecommerce"
    And I change the Taille to "4,9 METER"
    And I save the product
    Then I should see validation tooltip "Cette valeur ne doit pas être un nombre décimal."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the number min constraint of scopable metric attribute
    Given I switch the scope to "ecommerce"
    And I change the Vitesse to "-7,5 YARD_PER_HOUR"
    And I save the product
    Then I should see validation tooltip "Cette valeur doit être supérieure ou égale à 5.5."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the number max constraint of scopable metric attribute
    Given I switch the scope to "ecommerce"
    And I change the Vitesse to "111,1 YARD_PER_HOUR"
    And I save the product
    Then I should see validation tooltip "Cette valeur doit être inférieure ou égale à 100."
    And there should be 1 error in the "[other]" tab

  Scenario: Validate the decimal separator constraint of metric attribute
    Given I switch the scope to "ecommerce"
    And I change the Vitesse to "50.1 YARD_PER_HOUR"
    And I save the product
    Then I should see validation error "Ce type de valeur attend , comme séparateur de décimales."
    And there should be 1 error in the "[other]" tab
