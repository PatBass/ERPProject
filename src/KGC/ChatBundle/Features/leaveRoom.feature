Feature: Leave conversation

    Scenario: Leave a conversation
        Given The following logged people exist:
            | type | username | password | chat_type | website |
            | psychic | Spyboy | jamesbond007 | minute | |
            | client | Marc | martinmatin | | voyance-par-tchat |

        When With "Marc", I choose a "discovery" formula rate on "voyance-par-tchat"
        Then With "Marc", I call authenticated url "/client/get-available-psychics/voyance-par-tchat"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"

        Then With "Marc", I ask a conversation with a random psychic available
        Then the response should be json object with status "OK"
        And the object "room" should be formated as "room.json"

        Then With "Spyboy", I "accept" to join the conversation
        Then the response should be json object with status "OK"
        And the response should be formated as "answer.json"

        Then "Marc" leave this conversation
        And the consumed time for this room should be 0 seconds

        #Then "Marc" restart this conversation
        #And this room should be "open"

    Scenario: Leave after n seconds
        Given The following logged people exist:
            | type | username | password | chat_type | website |
            | psychic | Spyboy | jamesbond007 | minute | |
            | client | Marc | martinmatin | | voyance-par-tchat |

        When With "Marc", I choose a "discovery" formula rate on "voyance-par-tchat"
        Then With "Marc", I call authenticated url "/client/get-available-psychics/voyance-par-tchat"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"

        Then With "Marc", I ask a conversation with a random psychic available
        Then the response should be json object with status "OK"
        And the object "room" should be formated as "room.json"

        Then With "Spyboy", I "accept" to join the conversation
        Then the response should be json object with status "OK"
        And the response should be formated as "answer.json"

        Then Wait 6 seconds

        Then "Marc" leave this conversation
        And the consumed time for this room should be 6 seconds

    Scenario: Leave after n seconds when second psychic
        Given The following logged people exist:
            | type | username | password | chat_type | website | is_available |
            | psychic | SpyGeorge | george007 | minute | | yes |
            | psychic | SpyLuc | luc007 | minute | | no |
            | client | Marc | martinmatin | | voyance-par-tchat | |

        When With "Marc", I choose a "discovery" formula rate on "voyance-par-tchat"
        Then With "Marc", I call authenticated url "/client/get-available-psychics/voyance-par-tchat"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"

        Then With "Marc", I ask a conversation with a random psychic available
        Then the response should be json object with status "OK"
        And the object "room" should be formated as "room.json"

        # Spyluc is unavailable at start, so we are sure the "psysical" psychic choosen is SpyGeorge
        Then With "SpyLuc", I call authenticated url "/psychic/set-availability/1"

        Then With "SpyGeorge", I "refuse" to join the conversation
        Then the response should be json object with status "OK"
        And the response should be formated as "answer.json"
        And the object "new_psychic" should be formated as "user.json"

        Then With "SpyLuc", I "accept" to join the conversation
        Then the response should be json object with status "OK"
        And the response should be formated as "answer.json"

        Then Wait 6 seconds

        Then "Marc" leave this conversation
        And the consumed time for this room should be 6 seconds

