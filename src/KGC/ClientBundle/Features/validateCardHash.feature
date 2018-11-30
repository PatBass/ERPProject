Feature: Validate card hash
    Scenario: Validate not existing hash
        Given I have a client named "Julien" with password "pwd007"
        When I call url "/card/validateHash/tartempionNotExisting"
        Then the JSON response should be equal to:
        """
        {
            "success": false,
            "message": "Invalid hash"
        }
        """

    Scenario: Validate expire card hash
        Given I have a client named "Julien" with password "pwd007"
        And "Julien" has a consultation with expired new card hash "tartempionExpired"
        When I call url "/card/validateHash/tartempionExpired"
        Then the JSON response should be equal to:
        """
        {
            "success": false,
            "message": "Expired hash"
        }
        """

    Scenario: Validate not expired hash
        Given I have a client named "Julien" with password "pwd007"
        And "Julien" has a consultation with new card hash "tartempionNotExpired"
        When I call url "/card/validateHash/tartempionNotExpired"
        Then the JSON response should be equal to:
        """
        {
            "success": true
        }
        """
