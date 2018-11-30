Feature: Get websites formulas
    In order to show formulas
    As a client on website
    I need to be able to see which formulas are availables

    Scenario: Get website formulas
        When I call url "/open/website/univers-de-luxe/get-formulas"

        Then the response should be json object with status "OK"
        And the object "formulas" should be formated as "formulas.json"
        And the JSON field "aliases" should be an empty array

    Scenario: Get client formulas
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | univers-de-luxe |

        When With "Julien", I call authenticated url "/client/univers-de-luxe/get-formulas"
        Then the response should be json object with status "OK"
        And the object "formulas" should be formated as "formulas.json"
        And the JSON field "aliases" should be an empty array

    Scenario: Get website credit card
        Given The following logged people exist:
            | type | username | password | website |
            | client | Michel | martinmatin | univers-de-luxe |
        And "Michel" has a "valid" credit card on "univers-de-luxe"

        When With "Michel", I call authenticated url "/client/univers-de-luxe/get-formulas"
        Then the response should be json object with status "OK"
        And the object "formulas" should be formated as "formulas.json"
        And the object "cards" should be formated as "aliases.json"

    Scenario: Get free offer formulas
        When I call url "/open/website/tarot-en-direct/get-free-offer-formula-rate"
        Then the response should be json object with status "OK"
        And the JSON response should be equal to:
        """
            {
                "status": "OK",
                "message": "Formulas retrieved",
                "formulaRate": {
                    "id": 137,
                    "unit": 300,
                    "bonus": 0,
                    "price": 0,
                    "is_discovery": false,
                    "is_standard": false,
                    "is_premium": false,
                    "is_subscription": false,
                    "is_free_offer": true
                }
            }
        """