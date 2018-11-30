Feature: Delete an existing alias
    When a credit card is obsolete
    Or is just unused anymore
    It should be deletable

    Scenario: Delete a non existing credit card
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | voyance-des-iles |
        And "Marc" has no credit card on "voyance-des-iles"

        And With "Marc", I try to delete this credit card
        And the response should be json object with status "KO"
        And the JSON field "message" should be equal to "Invalid card"

    Scenario: Delete a credit card belonging to another client
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | voyance-des-iles |
            | client | Paul | mccartney | voyance-des-iles |
        And "Paul" has a "valid" credit card on "voyance-des-iles"

        When With "Marc", I try to delete this credit card
        Then the response should be json object with status "KO"
        And the JSON field "message" should be equal to "Invalid card"

    Scenario: Delete a credit card belonging to the proper client
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | voyance-des-iles |
        And "Marc" has a "valid" credit card on "voyance-des-iles"

        When With "Marc", I try to delete this credit card
        Then the response should be json object with status "OK"
        And the JSON field "message" should be equal to "Card deleted"
        And this credit card should not exist anymore

    Scenario: Delete the last credit card with a subscription
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | mon-amour-voyance |

        When With "Marc", I choose a "random" formula rate on "mon-amour-voyance" with "valid" card
        Then the response should be json object with status "OK"

        When With "Marc", I try to delete this credit card
        Then the response should be json object with status "KO"




