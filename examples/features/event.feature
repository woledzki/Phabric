Feature: Phabric Example
     As a Phabric user I want to add some rows to the database
     from a Gherkin table. I want to keep the column names and values in
     a human readable format.

Scenario:
    Given The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |
    When I select all records from the event table
    Then I should see the following records
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |


