var classes = [
    {
        "name": "Snicco\\Component\\TestableClock\\SystemClock",
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
                "name": "currentTimestamp",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "currentTime",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "fromUTC",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 4,
        "nbMethods": 4,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 4,
        "nbMethodsGetter": 0,
        "nbMethodsSetters": 0,
        "wmc": 5,
        "ccn": 2,
        "ccnMethodMax": 2,
        "externals": [
            "Snicco\\Component\\TestableClock\\Clock",
            "DateTimeZone",
            "DateTimeImmutable",
            "DateTimeImmutable",
            "Snicco\\Component\\TestableClock\\SystemClock",
            "DateTimeZone"
        ],
        "parents": [],
        "implements": [
            "Snicco\\Component\\TestableClock\\Clock"
        ],
        "lcom": 2,
        "length": 11,
        "vocabulary": 6,
        "volume": 28.43,
        "difficulty": 1.75,
        "effort": 49.76,
        "level": 0.57,
        "bugs": 0.01,
        "time": 3,
        "intelligentContent": 16.25,
        "number_operators": 4,
        "number_operands": 7,
        "number_operators_unique": 2,
        "number_operands_unique": 4,
        "cloc": 0,
        "loc": 21,
        "lloc": 21,
        "mi": 60.71,
        "mIwoC": 60.71,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 4,
        "relativeDataComplexity": 1.08,
        "relativeSystemComplexity": 5.08,
        "totalStructuralComplexity": 16,
        "totalDataComplexity": 4.33,
        "totalSystemComplexity": 20.33,
        "package": "Snicco\\Component\\TestableClock\\",
        "pageRank": 0,
        "afferentCoupling": 9,
        "efferentCoupling": 5,
        "instability": 0.36,
        "violations": {}
    },
    {
        "name": "Snicco\\Component\\TestableClock\\TestClock",
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
                "name": "travelIntoFuture",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "travelIntoPast",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "currentTimestamp",
                "role": null,
                "public": true,
                "private": false,
                "_type": "Hal\\Metric\\FunctionMetric"
            },
            {
                "name": "currentTime",
                "role": "getter",
                "_type": "Hal\\Metric\\FunctionMetric"
            }
        ],
        "nbMethodsIncludingGettersSetters": 5,
        "nbMethods": 4,
        "nbMethodsPrivate": 0,
        "nbMethodsPublic": 4,
        "nbMethodsGetter": 1,
        "nbMethodsSetters": 0,
        "wmc": 5,
        "ccn": 2,
        "ccnMethodMax": 2,
        "externals": [
            "Snicco\\Component\\TestableClock\\Clock",
            "DateTimeImmutable",
            "DateTimeImmutable",
            "DateInterval",
            "DateInterval",
            "DateTimeImmutable"
        ],
        "parents": [],
        "implements": [
            "Snicco\\Component\\TestableClock\\Clock"
        ],
        "lcom": 1,
        "length": 28,
        "vocabulary": 8,
        "volume": 84,
        "difficulty": 6,
        "effort": 504,
        "level": 0.17,
        "bugs": 0.03,
        "time": 28,
        "intelligentContent": 14,
        "number_operators": 8,
        "number_operands": 20,
        "number_operators_unique": 3,
        "number_operands_unique": 5,
        "cloc": 0,
        "loc": 27,
        "lloc": 27,
        "mi": 55.03,
        "mIwoC": 55.03,
        "commentWeight": 0,
        "kanDefect": 0.15,
        "relativeStructuralComplexity": 9,
        "relativeDataComplexity": 0.65,
        "relativeSystemComplexity": 9.65,
        "totalStructuralComplexity": 45,
        "totalDataComplexity": 3.25,
        "totalSystemComplexity": 48.25,
        "package": "Snicco\\Component\\TestableClock\\",
        "pageRank": 0,
        "afferentCoupling": 2,
        "efferentCoupling": 3,
        "instability": 0.6,
        "violations": {}
    }
]