Feature: Use promotion code
    In order to buy credits
    As a client
    I need to be able to choose a formula rate on a specific website

    Scenario Outline: Use promotion code
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | <website> |

        When There is a "<promotionType>" promotion with code "TARTEMPION" on "<website>"
        And With "Marc", I choose a "discovery" formula rate on "<website>" with "valid" card
        Then the response should be json object with status "OK"
        And the JSON field "message" should be equal to "Formula rate chose"
        When With "Marc", I choose a "standard" formula rate on "<website>" with "valid" card and code "TARTEMPION"
        Then the response should be json object with status "OK"
        And the JSON field "message" should be equal to "Formula rate chose"
        And the JSON field "promotion/type" should be equal to "<resultType>"
        And the JSON field "promotion/unit" should be equal to "<resultUnit>"
        Examples:
            | website           | createType | resultType     | resultUnit |
            | mon-amour-voyance | bonus      | bonus_question | 10         |
            | tarot-en-direct   | bonus      | bonus_duration | 30         |
            | tarot-en-direct   | percentage | percentage     | 10         |
            | tarot-en-direct   | price      | price          | 5          |