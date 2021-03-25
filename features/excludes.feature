Feature: Testing if exclude will not download translations for matches
  See: ./tests/fixtures/excludes/composer.json

  Scenario: Installing with auto-run disabled
    Given I am using the fixtures "excludes"
    When I run composer install
    Then I should see the folder "languages" exists
    And I should see the file "languages/de_DE.mo" does not exist