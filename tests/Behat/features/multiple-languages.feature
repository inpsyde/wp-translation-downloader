Feature: Testing, if we're able to download WordPress + multiple languages
  and additionally an not found language will not break anything.
  See: ./tests/fixtures/multiple-languages/composer.json

  Scenario: Installing "multiple languages"
	Given I am using the fixtures "multiple-languages"
	When I run composer install
	Then I should see in console "wordpress-core: found 2 translation"
	And I should see the file "languages/de_DE.mo" exists
	And I should see the file "languages/en_GB.mo" exists