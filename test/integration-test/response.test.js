let request = require('supertest');
request = request('https://www.google.se');

test('Should return 200 response code', async done => {
  const response = await request.get('/');
  expect(response.status).toBe(200);
  done();
});