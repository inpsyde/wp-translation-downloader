Feature: Testing if virtual packages translations are downloaded as well.
  See: ./tests/fixtures/virtual-packages/composer.json

  Scenario: Installing with virtual packages
    Given I am using the fixtures "virtual-packages"
    When I run composer install
    Then I should see the folder "languages" exists
	Then I should see in console "backwpup: found 1 translations"
	And I should see in console "wordpress: found 1 translations"
	And I should see the file "languages/de_DE.mo" exists