var classes = [
    {
        "name": "Snicco\\Bridge\\SignedUrlPsr16\\Psr16Storage",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": "setter",
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "consume",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "store",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "gc",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "buildCacheKey",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "ttlInSeconds",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getData",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 7,
        "nbMethods": 6,
        "nbMethodsPrivate": 3,
        "nbMethodsPublic": 3,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 18,
        "ccn": 13,
        "ccnMethodMax": 9,
        "externals": [
            "Snicco\\Component\\SignedUrl\\Storage\\SignedUrlStorage",
            "Psr\\SimpleCache\\CacheInterface",
            "Snicco\\Component\\SignedUrl\\Exception\\UnavailableStorage",
            "Snicco\\Component\\SignedUrl\\Exception\\UnavailableStorage",
            "Snicco\\Component\\SignedUrl\\SignedUrl",
            "Snicco\\Component\\SignedUrl\\Exception\\UnavailableStorage",
            "Snicco\\Component\\SignedUrl\\Exception\\BadIdentifier",
            "RuntimeException",
            "RuntimeException"
        ],
        "parents": [],
        "implements": [
            "Snicco\\Component\\SignedUrl\\Storage\\SignedUrlStorage"
        ],
        "lcom": 2,
        "length": 145,
        "vocabulary": 35,
        "volume": 743.75,
        "difficulty": 21.6,
        "effort": 16064.91,
        "level": 0.05,
        "bugs": 0.25,
        "time": 892,
        "intelligentContent": 34.43,
        "number_operators": 37,
        "number_operands": 108,
        "number_operators_unique": 10,
        "number_operands_unique": 25,
        "cloc": 9,
        "loc": 73,
        "lloc": 64,
        "mi": 64.62,
        "mIwoC": 38.75,
        "commentWeight": 25.88,
        "kanDefect": 0.64,
        "relativeStructuralComplexity": 121,
        "relativeDataComplexity": 0.33,
        "relativeSystemComplexity": 121.33,
        "totalStructuralComplexity": 847,
        "totalDataComplexity": 2.33,
        "totalSystemComplexity": 849.33,
        "package": "Snicco\\Bridge\\SignedUrlPsr16\\",
        "pageRank": 0,
        "afferentCoupling": 0,
        "efferentCoupling": 6,
        "instability": 1,
        "violations": {}
    }
]