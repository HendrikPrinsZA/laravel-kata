import http from 'k6/http';
import exec from 'k6/execution';
import { sleep } from 'k6';

/**
 * Run with `npm run reset && k6 run bin/k6-stress.js`
 */
export const options = {
  thresholds: {
    http_req_failed: ['rate<=0'],
  },
  stages: [
    { target: 100, duration: '5s' },
    { target: 1000, duration: '10s' },
  ],
};

export function handleSummary(data) {
  console.log(data.metrics.http_req_duration.values);
  return {
    stdout: 'Sample custom summary!',
    stderr: 'Sample custom error!',
    // 'summary.json': JSON.stringify(data.metrics.iterations),
  }
}

export default function () {
  let iteration = exec.scenario.iterationInTest;

  if (iteration > 1000) {
    iteration = 1000;
  }

  // const option = 'A';
  // const challenge = 'Sample';
  // const method = 'fizzBuzz';
  //  - A: {"avg":10703.058748527677,"min":12.198,"med":7932.6535,"max":30431.667,"p(90)":25939.6588,"p(95)":28190.9777}
  //  - B: {"avg":5151.5332029278115,"min":11.646,"med":5001.74,"max":11646.519,"p(90)":10433.282,"p(95)":11055.093}

  // const option = 'A';
  // const challenge = 'Sample';
  // const method = 'calculatePi';
  //  - A: {"avg":7617.119875000003,"min":17.119,"med":7502.202,"max":16924.24,"p(90)":15086.9375,"p(95)":16010.314949999998}
  //  - B: {"avg":5093.825708058794,"min":11.931,"med":4992.803,"max":11386.017,"p(90)":10214.7732,"p(95)":10793.5666}

  const option = 'A';
  const challenge = 'Eloquent';
  const method = 'getCollectionAverage';
  //  - A: {"avg":17228.96428473804,"min":511.68,"med":16546.62,"max":36948.169,"p(90)":32979.1318,"p(95)":34967.0796}
  //  - B: {"avg":6444.613970049914,"min":15.013,"med":6344.967,"max":14372.692,"p(90)":12789.5128,"p(95)":13591.011999999999}

  http.get(`http://localhost/api/kata/${option}/${challenge}/${method}?iteration=${iteration}`);
  sleep(1);
};
