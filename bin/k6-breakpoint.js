import http from 'k6/http';
import exec from 'k6/execution';
import { sleep } from 'k6';

/**
 * Run with `npm run reset && k6 run bin/k6-breakpoint.js`
 */
export const options = {
  discardResponseBodies: true,
  thresholds: {
    http_req_failed: ['rate<=0'],
    http_req_duration: ['p(95)<200'],
  },
  scenarios: {
    challenge: {
      executor: 'ramping-arrival-rate',
      startRate: 100,
      preAllocatedVUs: 100,
      maxVUs: 1000,
      stages: [
        { target: 100, duration: '10s' },
        { target: 800, duration: '20s' },
      ],
    },
  },
};

export function handleSummary(data) {
  console.log(data.metrics.iterations.values);
  return {
    stdout: 'Sample custom summary!',
    stderr: 'Sample custom error!',
    // 'summary.json': JSON.stringify(data.metrics.iterations),
  }
}

export default function () {
//   let iteration = __ITER + 1;
  let iteration = exec.scenario.iterationInTest;

  if (iteration > 1000) {
    iteration = 1000;
  }

  //  const option = 'A';
  //  const challenge = 'Sample';
  //  const method = 'fizzBuzz';
  //  - A: {"count":2069,"rate":34.4648687798845}
  //  - B: {"count":2439,"rate":57.28260885903377}

  //  const option = 'A';
  //  const challenge = 'Sample';
  //  const method = 'calculatePi';
  //  - A: {"count":2584,"rate":52.287087596472865}
  //  - B: {"count":2954,"rate":68.02911904225319}

   const option = 'A';
   const challenge = 'Eloquent';
   const method = 'getCollectionAverage';
   //  - A: {"count":587,"rate":9.775582925337362}
   //  - B: {"count":2855,"rate":63.35575342917176}

  http.get(`http://localhost/api/kata/${option}/${challenge}/${method}?iteration=${iteration}`);
  sleep(1);
};
