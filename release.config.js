module.exports = {

    branches: [
        "master"
    ],
    plugins: [
        ["@semantic-release/commit-analyzer", {
            "preset": "angular",
            "parserOpts": {
                "noteKeywords": ["BREAKING CHANGE", "BREAKING CHANGES"]
            }
        }],
        ["@semantic-release/release-notes-generator", {
            "preset": "angular",
            "parserOpts": {
                "noteKeywords": ["BREAKING CHANGE", "BREAKING CHANGES", "BREAKING"]
            },
            "writerOpts": {
                "commitsSort": ["scope", "subject"]
            }
        }],
        "@semantic-release/github",
        "@semantic-release/git"
    ],
}