Feature: Testing with auto-run=false to run download and cleanup commands.
  See: ./tests/fixtures/commands/composer.json

  Scenario: Installing without downloading
	Given I am using the fixtures "commands"
	When I run composer install
	Then I should see the folder "languages" exists
	And I should see the file "languages/de_DE.mo" does not exist

  Scenario: Installing translations via CLI
	Given I am using the fixtures "commands"
	When I run composer wp-translation-downloader:download
	Then I should see the file "languages/de_DE.mo" exists

  Scenario: Cleanup translations via CLI
	Given I am using the fixtures "commands"
	When I run composer wp-translation-downloader:clean-up
	Then I should see the file "languages/de_DE.mo" does not exist

  Scenario: Clean cache via CLI
	Given I am using the fixtures "commands"
	When I run composer wp-translation-downloader:clean-cache
	Then I should see in console "Starting to clean cache directory."
	And I should see in console "Cache folder was emptied successfully."