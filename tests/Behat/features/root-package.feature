Feature: Testing, if translations are downloaded for a root-package as well.
  To achieve that we use in composer.json the "name": "wpackagist-plugin/backwpup",
  which should download translations for this root level package as well.
  See: ./tests/fixtures/directories/composer.json

  Scenario: Installing "root package" translations
	Given I am using the fixtures "root-package"
	When I run composer install
	Then I should see in console "backwpup: found one translation"
	And I should see the file "languages/plugins/backwpup-de_DE.mo" exists
	Then I should see in console "wordpress-core: found one translation"
	And I should see the file "languages/de_DE.mo" exists