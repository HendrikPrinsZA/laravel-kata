import http from "k6/http";
import { sleep } from 'k6';

/**
 * Integrate load tests with CI/CD
 *
 * Refs
 * - https://k6.io/blog/integrating-load-testing-with-circleci/
 * - https://circleci.com/developer/orbs/orb/k6io/test
 */

const URL = 'http://localhost/api/kata/challenge/KataChallengeEloquent/getModelUnique';
// const URL = 'http://localhost/api/kata/challenge/KataChallengeEloquentRecord/getModelUnique';

export let options = {
    vus: 5,
    stages: [
        { duration: "10s", target: 10 },
        { duration: "30s", target: 20 },
        { duration: "1m", target: 100 },
        { duration: "30s", target: 0 },
    ]
};

export function setup() {
  return {
    'token': '123456'
  }
}

export default function (data) {
  http.get(URL);
  sleep(1);
}

export function teardown(data) {
//   console.log('teardown()');
}
