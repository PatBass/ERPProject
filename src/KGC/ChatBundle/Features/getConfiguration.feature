Feature: Get configuration
    In order to work
    As node server
    I need to be able to get default configuration from kgestion

    Scenario: Get configuration

        When I call url "/open/node/get-configuration"
        Then the response should be json object with status "OK"
