Feature: Testing, if we're able to configure directories by "type" and "name"
  See: ./tests/fixtures/directories/composer.json

  Scenario: Installing "into custom directories"
	Given I am using the fixtures "custom-directories"
	When I run composer install
	Then I should see in console "wordpress-core: found one translation"
	And I should see the file "languages/core/de_DE.mo" exists
	Then I should see in console "backwpup: found one translation"
	And I should see the file "languages/inpsyde/backwpup-de_DE.mo" exists