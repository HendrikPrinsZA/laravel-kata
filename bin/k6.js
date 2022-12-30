import http from "k6/http";
import { sleep, check } from "k6";

/**
 * Integrate load tests with CI/CD
 *
 * Refs
 * - https://k6.io/blog/integrating-load-testing-with-circleci/
 * - https://circleci.com/developer/orbs/orb/k6io/test
 * - https://k6.io/docs/examples/advanced-api-flow/
 */
const CLASS = 'KataChallengeSample';
const METHOD = 'calculatePi';
const MODE = 'Before';
// const MODE = 'Record';

const VUS_DEFAULT = 5;
const VUS_MAX = 200;

export const options = {
    vus: VUS_DEFAULT,
    stages: [
        { duration: "5s", target: 10 },
        { duration: "1m", target: Math.floor(VUS_MAX / 5) },
        { duration: "3m", target: VUS_MAX },
        { duration: "30s", target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(90) < 400'],
    },
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
        const response = http.request('GET', url, params, requestConfigWithTag({
            name: 'Before'
        }));

        check(response, { "status is 200": (r) => r.status === 200 });
    }

    if (MODE === 'Record') {
        const classRecord = `${CLASS}Record`;
        const url = `http://localhost/api/kata/${classRecord}/${METHOD}`;
        const params = {
            iterations
        }
        const response = http.request('GET', url, params, requestConfigWithTag({
            name: 'Record'
        }));

        check(response, { "status is 200": (r) => r.status === 200 });
    }

    sleep(.300);
};
