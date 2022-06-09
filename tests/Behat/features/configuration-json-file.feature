Feature: Testing if the given example from README.md with configuration
  via JSON-file runs and downloads WordPress + germany translation-set.
  See: ./tests/fixtures/configuration-json-file/composer.json

  Scenario: Installing with additional JSON file
	Given I am using the fixtures "configuration-json-file"
	When I run composer install
	Then I should see the folder "languages" exists
    And I should see the file "languages/de_DE.mo" exists