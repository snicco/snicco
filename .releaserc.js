module.exports = {
    "branches": [
        "master"
    ],
    "repositoryUrl": "https://github.com/sniccowp/sniccowp.git",
    "tagFormat": "v${version}",
    "plugins": [
        ["@semantic-release/commit-analyzer", {
            "preset": "angular",
            "parserOpts": {
                "noteKeywords": ["BREAKING CHANGE", "BREAKING CHANGES", "BREAKING"]
            }
        }],
        ["@semantic-release/release-notes-generator", {
            "preset": "angular",
            "parserOpts": {
                "noteKeywords": ["BREAKING CHANGE", "BREAKING CHANGES", "BREAKING"]
            },
            "writerOpts": {
                groupBy: 'type',
                commitGroupsSort: 'title',
                commitsSort: ['scope', 'subject'],
                noteGroupsSort: 'title',
            }
        }],
        "@semantic-release/changelog",
        "@semantic-release/github",
        ["@semantic-release/git", {
            "assets": [
                "CHANGELOG.md",
                "src/**/composer.json",
                "monorepo-builder.php"
            ],
            "message": "chore(release): ${nextRelease.version} [skip ci]"
        }]
    ]
}