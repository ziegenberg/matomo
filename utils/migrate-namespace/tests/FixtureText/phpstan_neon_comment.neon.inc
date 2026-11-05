    ignoreErrors:
        - "#Unsafe usage of new static#"
        # Monolog\Logger and Symfony's ConsoleFormatter are marked @final only via
        # a docblock annotation, not the final keyword. Both are extended on purpose:
        # Piwik\Log\Logger is a proxy that adds Matomo's logger interface, and
        # ConsoleFormatter augments the console output with %extra.*% placeholders.
        # These are deliberate, long-standing integrations rather than accidental
        # subclassing, so the soft-final hint is intentionally overridden here.
-----
    ignoreErrors:
        - "#Unsafe usage of new static#"
        # Monolog\Logger and Symfony's ConsoleFormatter are marked @final only via
        # a docblock annotation, not the final keyword. Both are extended on purpose:
        # Matomo\Log\Logger is a proxy that adds Matomo's logger interface, and
        # ConsoleFormatter augments the console output with %extra.*% placeholders.
        # These are deliberate, long-standing integrations rather than accidental
        # subclassing, so the soft-final hint is intentionally overridden here.
