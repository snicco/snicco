module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            [
                'build',
                'ci',
                'docs',
                'feat',
                'fix',
                'perf',
                'refactor',
                'style',
                'revert',
                'test',
            ],
        ],
        'subject-case': [2, 'always', 'lower-case'],
        'subject-exclamation-mark': [2, 'never', '!'],
        'scope-case': [2, 'always', 'lower-case'],
        'header-max-length': [2, 'always', 72],
        'body-max-line-length': [2, 'always', 80],
        'body-case': [2, 'always', 'sentence-case'],
        'body-full-stop': [2, 'always', '.'],
        'footer-max-line-length': [2, 'always', 80],
    },
    prompt: {
        messages: {
            skip: '(skip)',
            max: 'max %d chars',
            min: 'min %d chars',
            emptyWarning: 'can not be empty',
            upperLimitWarning: 'over limit',
            lowerLimitWarning: 'below limit'
        },
        "questions": {
            body: {
                description: 'Provide a longer description of the change (max. 80 chars per line. Use \\n to insert a new line.).',
            }
        }
    }
};
