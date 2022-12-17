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

// const target_vus_env = `${__ENV.TARGET_VUS}`;
// const target_vus = isNumeric(target_vus_env) ? Number(target_vus_env) : default_vus;

const VUS_DEFAULT = 5;
const VUS_MAX = 200;

export const options = {
    vus: VUS_DEFAULT,
    stages: [
        { duration: "5s", target: 10 },
        { duration: "1m", target: Math.floor(VUS_MAX / 3) },
        { duration: "2m", target: VUS_MAX },
        { duration: "5s", target: 0 },
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

    sleep(1);
};
