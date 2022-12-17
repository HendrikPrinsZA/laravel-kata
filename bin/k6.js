import http from "k6/http";
import { sleep } from "k6";

/**
 * Integrate load tests with CI/CD
 *
 * Refs
 * - https://k6.io/blog/integrating-load-testing-with-circleci/
 * - https://circleci.com/developer/orbs/orb/k6io/test
 * - https://k6.io/docs/examples/advanced-api-flow/
 */
const CLASS = 'KataChallengeEloquent';
const METHOD = 'getCollectionCount';
const MODE = 'Before';
// const MODE = 'Record';

export const options = {
    vus: 5,
    stages: [
        { duration: "10s", target: 10 },
        { duration: "1m", target: 50 },
        { duration: "30s", target: 0 },
    ],
    // thresholds: {
    //   'http_req_duration': ['p(95)<20000', 'p(99)<30000'],
    // },
    ext: {
        loadimpact: {
            projectID: 3620115,
            name: CLASS,
        },
    },
};

// Placeholder for authentication
export function setup() {
    return {
        token: "123456",
    };
}

let iterations = 1;

export default (authToken) => {
    const requestConfigWithTag = (tag) => ({
        headers: {
            Authorization: `Bearer ${authToken}`,
        },
        tags: Object.assign(
            {},
            {
                name: 'DefaultTag',
            },
            tag
        ),
        tag
    });

    const payload = {};

    iterations++;

    if (MODE === 'Before') {
        const url = `http://localhost/api/kata/${CLASS}/${METHOD}`;
        const params = {
            iterations
        }
        const res = http.request('GET', url, params, requestConfigWithTag({
            name: 'Before'
        }));
    }

    if (MODE === 'Record') {
        const classRecord = `${CLASS}Record`;
        const url = `http://localhost/api/kata/${classRecord}/${METHOD}`;
        const params = {
            iterations
        }
        const res = http.request('GET', url, params, requestConfigWithTag({
            name: 'Record'
        }));
    }

    sleep(1);
};
