Feature: Buy a formula rate
    In order to buy credits
    As a client
    I need to be able to choose a formula rate on a specific website

    Scenario: Buy discovery formula rate on subscription website
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | voyance-des-iles |

        When With "Marc", I choose a "discovery" formula rate on "voyance-des-iles" with "valid" card
        Then the response should be json object with status "OK"
        And the object "formula_rate" should be formated as "formula_rate.json"

        When With "Marc", I call authenticated url "/client/voyance-des-iles/subscription/get"
        Then the response should be json object with status "OK"
        And the object "subscriptions" should be formated as "subscriptions.json"
        And the object "subscriptions" should contains 1 elements

    Scenario: Buy formula rate with wrong client
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | univers-de-luxe |
        When With "Marc", I choose a "random" formula rate on "anastazia"
        Then the response should be json object with status "KO"
        And the JSON field "message" should be equal to "Invalid client origin"
        And the JSON field "formula_rate" should be null

    Scenario: Buy formula rate with expired formula
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | univers-de-luxe |

        When With "Marc", I choose a formula rate with expired formula on "univers-de-luxe"
        Then the response should be json object with status "KO"
        And the JSON field "message" should be equal to "Expired formula"
        And the JSON field "formula_rate" should be null

    Scenario: Buy formula rate with expired formula rate
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | univers-de-luxe |

        When With "Marc", I choose a formula rate with expired formula rate on "univers-de-luxe"
        Then the response should be json object with status "KO"
        And the JSON field "message" should be equal to "Expired formula rate"
        And the JSON field "formula_rate" should be null

    Scenario Outline: Buy first formula rate
        Given The following logged people exist:
            | type | username | password | website |
            | client | Michel | martinmatin | mon-amour-voyance |

        When With "Michel", it should be "<status>" to choose a "<type>" formula rate on "mon-amour-voyance"

        Examples:
            | type      | status |
            | discovery | OK     |
            | standard  | KO     |
            | premium   | KO     |

    Scenario Outline: Buy formula rate when already having bought one
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | voyance-des-iles |

        Then With "Marc", I choose a "<existing>" formula rate on "voyance-des-iles"
        And With "Marc", it should be "<status>" to choose a "<new>" formula rate on "voyance-des-iles"

        Examples:
            | existing  | new       | status |
            | discovery | discovery | KO     |
            | discovery | standard  | OK     |
            | discovery | premium   | KO     |

    Scenario Outline: Buy formula rate with card
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | voyance-des-iles |

        When With "Marc", I choose a "random" formula rate on "voyance-des-iles" with "<validity>" card
        Then the response should be json object with status "<status>"
        And the JSON field "message" should be equal to "<message>"

        Examples:
            | validity | status | message            |
            | invalid  | KO     | Payment refused    |
            | valid    | OK     | Formula rate chose |

    Scenario Outline: Buy formula rate with promotion code
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