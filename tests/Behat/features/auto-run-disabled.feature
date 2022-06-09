Feature: Testing if disabled auto-run setting will not download
  anything and just creating an empty "languages" folder.
  See: ./tests/fixtures/disable-autorun/composer.json

  Scenario: Installing with auto-run disabled
	Given I am using the fixtures "auto-run-disabled"
	When I run composer install
	Then I should see the folder "languages" does not exist
	And I should see the file "languages/de_DE.mo" does not exist