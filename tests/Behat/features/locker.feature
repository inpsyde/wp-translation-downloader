Feature: Testing if lock file is working as expected
  See: ./tests/fixtures/locker/composer.json

  Scenario: Installing the first time
	Given I am using the fixtures "locker"
	When I run composer install
	Then I should see the folder "languages" exists
	And I should see the file "wp-translation-downloader.lock" exists