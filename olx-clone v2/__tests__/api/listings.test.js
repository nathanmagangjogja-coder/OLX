import { createMocks } from 'node-mocks-http';
import listingsHandler from '../../lib/api/listings';
import { getDb } from '../../lib/db';
import { getSession } from '../../lib/session';

jest.mock('../../lib/db');
jest.mock('../../lib/session');
jest.mock('formidable', () => {
  return () => ({
    parse: (req, cb) => {
      cb(null, req.body.fields, req.body.files);
    },
  });
});

describe('/api/listings', () => {
  it('returns 401 if not logged in', async () => {
    getSession.mockResolvedValue(null);

    const { req, res } = createMocks({
      method: 'POST',
    });

    await listingsHandler(req, res);

    expect(res._getStatusCode()).toBe(401);
    expect(JSON.parse(res._getData())).toEqual({
      success: false,
      message: 'Login dulu',
    });
  });

  it('validates required fields for create action', async () => {
    getSession.mockResolvedValue({ user_id: 1 });
    const { req, res } = createMocks({
      method: 'POST',
      body: {
        fields: {
          action: 'create',
          title: '',
        },
        files: {},
      },
    });

    await listingsHandler(req, res);

    expect(res._getStatusCode()).toBe(400);
    const data = JSON.parse(res._getData());
    expect(data.success).toBe(false);
    expect(data.message).toContain('Kategori harus dipilih');
  });
});
