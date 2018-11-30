Feature: Validate card hash
    Scenario Outline: Create card with wrong parameters
        Given I have a client named "Julien" with password "pwd007"
        And "Julien" has a consultation with new card hash "tartempionNotExpired"
        When I call url "/card/create/tartempionNotExpired" with parameters:
        | number   | securityCode   | expireAt   |
        | <number> | <securityCode> | <expireAt> |
        Then the JSON response should be equal to:
        """
        {
            "success": false,
            "message": "<errorMessage>"
        }
        """
        And "Julien" should not have any credit card
        And "Julien" consultation should not be confirmed

        Examples:
            | number           | securityCode | expireAt                                | errorMessage                                      |
            |                  | 123          | {"day":"1", "month":"1", "year":"2025"} | number: Cette valeur ne doit pas être vide.       |
            | 5555555555554444 |              | {"day":"1", "month":"1", "year":"2025"} | securityCode: Cette valeur ne doit pas être vide. |
            | 5555555555554444 | 123          |                                         | expireAt: Cette valeur n'est pas valide.          |
            | 1234567890123456 | 123          | {"day":"1", "month":"1", "year":"2025"} | number: Numéro de carte invalide.                 |
            | 5555555555554444 | 123          | {"month":"1", "year":"2025"}            | expireAt: Cette valeur n'est pas valide.          |
            | 5555555555554444 | 123          | {"day":"1", "year":"2025"}              | expireAt: Cette valeur n'est pas valide.          |
            | 5555555555554444 | 123          | {"month":"1", "year":"2025"}            | expireAt: Cette valeur n'est pas valide.          |

    Scenario: Create card with already existing card
        Given I have a client named "Julien" with password "pwd007"
        And "Julien" has a consultation with existing card and new card hash "tartempionNotExpired"
        When I call url "/card/create/tartempionNotExpired" with parameters:
        | number           | securityCode | expireAt                                |
        | 5555555555554444 | 123          | {"day":"1", "month":"1", "year":"2025"} |
        Then the JSON response should be equal to:
        """
        {
            "success": false,
            "message": "Credit card already set"
        }
        """
        And "Julien" should not have 2 credit cards
        And "Julien" consultation should not be confirmed
