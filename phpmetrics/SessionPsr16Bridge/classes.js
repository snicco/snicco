var classes = [
    {
        "name": "Snicco\\Bridge\\SessionPsr16\\Psr16SessionDriver",
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
                "name": "readParts",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "writeParts",
                "role": null,
                "public": false,
                "private": true,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 8,
        "nbMethods": 8,
        "nbMethodsPrivate": 2,
        "nbMethodsPublic": 6,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 25,
        "ccn": 18,
        "ccnMethodMax": 14,
        "externals": [
            "Snicco\\Component\\Session\\Driver\\SessionDriver",
            "Psr\\SimpleCache\\CacheInterface",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\ValueObject\\SerializedSession",
            "Snicco\\Component\\Session\\Exception\\CouldNotDestroySession",
            "Snicco\\Component\\Session\\Exception\\CouldNotDestroySession",
            "Snicco\\Component\\Session\\Exception\\CouldNotReadSessionContent",
            "Snicco\\Component\\Session\\Exception\\UnknownSessionSelector",
            "Snicco\\Component\\Session\\Exception\\CouldNotReadSessionContent",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "InvalidArgumentException",
            "Snicco\\Component\\Session\\Exception\\CouldNotWriteSessionContent",
            "Snicco\\Component\\Session\\Exception\\CouldNotWriteSessionContent"
        ],
        "parents": [],
        "implements": [
            "Snicco\\Component\\Session\\Driver\\SessionDriver"
        ],
        "lcom": 2,
        "length": 155,
        "vocabulary": 29,
        "volume": 752.99,
        "difficulty": 23.81,
        "effort": 17928.26,
        "level": 0.04,
        "bugs": 0.25,
        "time": 996,
        "intelligentContent": 31.63,
        "number_operators": 30,
        "number_operands": 125,
        "number_operators_unique": 8,
        "number_operands_unique": 21,
        "cloc": 9,
        "loc": 88,
        "lloc": 79,
        "mi": 59.81,
        "mIwoC": 36.04,
        "commentWeight": 23.77,
        "kanDefect": 0.71,
        "relativeStructuralComplexity": 196,
        "relativeDataComplexity": 0.26,
        "relativeSystemComplexity": 196.26,
        "totalStructuralComplexity": 1568,
        "totalDataComplexity": 2.07,
        "totalSystemComplexity": 1570.07,
        "package": "Snicco\\Bridge\\SessionPsr16\\",
        "pageRank": 0,
        "afferentCoupling": 1,
        "efferentCoupling": 8,
        "instability": 0.89,
        "violations": {}
    }
]