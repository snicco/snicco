var classes = [
    {
        "name": "Snicco\\Bridge\\SessionWP\\WPDBSessionDriver",
        "interface": false,
        "abstract": false,
        "final": true,
        "methods": [
            {
                "name": "__construct",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "read",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "write",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroy",
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
                "name": "touch",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroyAll",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroyAllForUserId",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroyAllForUserIdExcept",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "getAllForUserId",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "createTable",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "exists",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "instantiate",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 13,
        "nbMethods": 13,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 11,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 20,
        "ccn": 8,
        "ccnMethodMax": 3,
        "externals": [
            "Snicco\\Component\\Session\\Driver\\UserSessionsDriver",
            "Snicco\\Component\\BetterWPDB\\BetterWPDB",
            "Snicco\\Component\\TestableClock\\Clock",
            "Snicco\\Component\\TestableClock\\SystemClock",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\Exception\\BadSessionID",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\Exception\\BadSessionID",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession"
        ],
        "parents": [],
        "lcom": 1,
        "length": 172,
        "vocabulary": 46,
        "volume": 950.05,
        "difficulty": 10.88,
        "effort": 10331.82,
        "level": 0.09,
        "bugs": 0.32,
        "time": 574,
        "intelligentContent": 87.36,
        "number_operators": 27,
        "number_operands": 145,
        "number_operators_unique": 6,
        "number_operands_unique": 40,
        "cloc": 25,
        "loc": 110,
        "lloc": 85,
        "mi": 69.65,
        "mIwoC": 35.99,
        "commentWeight": 33.66,
        "kanDefect": 0.52,
        "relativeStructuralComplexity": 256,
        "relativeDataComplexity": 0.43,
        "relativeSystemComplexity": 256.43,
        "totalStructuralComplexity": 3328,
        "totalDataComplexity": 5.53,
        "totalSystemComplexity": 3333.53,
        "package": "Snicco\\Bridge\\SessionWP\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 6,
        "instability": 0.86,
        "violations": {}
    },
    {
        "name": "Snicco\\Bridge\\SessionWP\\WPObjectCacheDriver",
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
                "name": "read",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "write",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "destroy",
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
                "name": "touch",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 6,
        "nbMethods": 5,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 5,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 1,
        "wmc": 6,
        "ccn": 1,
        "ccnMethodMax": 1,
        "externals": [
            "Snicco\\Component\\Session\\Driver\\SessionDriver",
            "Snicco\\Bridge\\SessionPsr16\\Psr16SessionDriver",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession"
        ],
        "parents": [],
        "lcom": 2,
        "length": 22,
        "vocabulary": 9,
        "volume": 69.74,
        "difficulty": 2.86,
        "effort": 199.25,
        "level": 0.35,
        "bugs": 0.02,
        "time": 11,
        "intelligentContent": 24.41,
        "number_operators": 2,
        "number_operands": 20,
        "number_operators_unique": 2,
        "number_operands_unique": 7,
        "cloc": 0,
        "loc": 28,
        "lloc": 28,
        "mi": 55.39,
        "mIwoC": 55.39,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 16,
        "relativeDataComplexity": 0.47,
        "relativeSystemComplexity": 16.47,
        "totalStructuralComplexity": 96,
        "totalDataComplexity": 2.8,
        "totalSystemComplexity": 98.8,
        "package": "Snicco\\Bridge\\SessionWP\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 3,
        "instability": 0.75,
        "violations": {}
    }
]