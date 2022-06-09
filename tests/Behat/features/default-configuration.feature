Feature: Default configuration from README.md works
  by just downloading WordPress + german translation-set.
  See: ./tests/fixtures/default-configuration/composer.json

  Scenario: Installing with minimal configuration
	Given I am using the fixtures "default-configuration"
	When I run composer install
	Then I should see in console "wordpress-core: found 1 translation"
	And I should see the file "languages/de_DE.mo" exists