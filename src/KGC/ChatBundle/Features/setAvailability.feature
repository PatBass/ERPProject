Feature: Set availability
    In order to take a break
    As a psychic
    I need to be able to be unavailable if I don't have any conversation on going

    Scenario: Set availability
        Given The following logged people exist:
            | type | username | password | chat_type | website |
            | client | Marc | martinmatin | | voyance-par-tchat |

        Given I have a psychic named "Spyboy" with password "jamesbond007" on chat type "minute"
        Then I log in with "Spyboy"

        Then With "Marc", I choose a "discovery" formula rate on "voyance-par-tchat"

        # Get psychics
        Then With "Marc", I call authenticated url "/client/get-available-psychics/voyance-par-tchat"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"
        # Our psychic should be unavailable so website should not have virtual psychics availables
        And there should be 0 psychics availables

        # Become available
        Then With "Spyboy", I call authenticated url "/psychic/set-availability/1"
        Then the response should be json object with status "OK"
        And the object "token" should be formated as "token.json"

        # Retry get psychics
        Then With "Marc", I call authenticated url "/client/get-available-psychics/voyance-par-tchat"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"
        # Our psychic should be available so website should have virtual psychics availables
        And there should be at least 1 psychics availables

        Then With "Marc", I ask a conversation with a random psychic available
        Then the response should be json object with status "OK"
        And the object "room" should be formated as "room.json"

        Then With "Spyboy", I "accept" to join the conversation
        Then the response should be json object with status "OK"

        # We have a conversation, psychic should not be able to be unavailable
        Then With "Spyboy", I call authenticated url "/psychic/set-availability/0"
        Then the response should be json object with status "KO"


