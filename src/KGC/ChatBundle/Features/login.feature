Feature: Log in
    In order to test the chat api
    As a psychic or client
    I need to be able to log in

    # Notes for developers : you can use scenario outline to replace username and password
    # But keep in mind that output is totally useless if a test fail : all steps are shown like they are ignored (but obviously not)
    # And you don't know what step failed (you just got a line where the test failed in Context.php file)

    Scenario: Log in as psychic to get token
        Given I have a psychic named "Spyboy" with password "jamesbond007"

        When With "Spyboy", I call authenticated url "/psychic/check-authentication"
        Then I should have json object
        But the response status code should be "401"

        Then I log in with "Spyboy"

        Then With "Spyboy", I call authenticated url "/psychic/check-authentication"
        And I should have json object
        And the response status code should be "200"
        And the json status should be "OK"

    Scenario: Log in as client to get token
        Given I have a client named "Jean" with password "TEst125!!fez" on website "univers-de-luxe"

        When With "Jean", I call authenticated url "/client/check-authentication"
        Then I should have json object
        But the response status code should be "401"

        Then I log in with "Jean"

        Then With "Jean", I call authenticated url "/client/check-authentication"
        And I should have json object
        And the response status code should be "200"
        And the json status should be "OK"