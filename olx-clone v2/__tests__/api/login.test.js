import { createMocks } from 'node-mocks-http';
import loginHandler from '../../lib/api/login';
import { getDb } from '../../lib/db';
import { setSession } from '../../lib/session';
import bcrypt from 'bcryptjs';

jest.mock('../../lib/db');
jest.mock('../../lib/session');

describe('/api/login', () => {
  it('returns 405 for non-POST requests', async () => {
    const { req, res } = createMocks({
      method: 'GET',
    });

    await loginHandler(req, res);

    expect(res._getStatusCode()).toBe(405);
    expect(JSON.parse(res._getData())).toEqual({
      success: false,
      message: 'Method not allowed',
    });
  });

  it('returns 400 for missing credentials', async () => {
    const { req, res } = createMocks({
      method: 'POST',
      body: {},
    });

    await loginHandler(req, res);

    expect(res._getStatusCode()).toBe(400);
    expect(JSON.parse(res._getData())).toEqual({
      success: false,
      message: 'Email dan password harus diisi',
    });
  });

  it('returns 401 for invalid credentials', async () => {
    const mockDb = {
      execute: jest.fn().mockResolvedValue([[]]),
    };
    getDb.mockResolvedValue(mockDb);

    const { req, res } = createMocks({
      method: 'POST',
      body: {
        email: 'test@example.com',
        password: 'wrongpassword',
      },
    });

    await loginHandler(req, res);

    expect(res._getStatusCode()).toBe(401);
    expect(JSON.parse(res._getData())).toEqual({
      success: false,
      message: 'Email atau password salah',
    });
  });

  it('returns 200 and sets session for valid credentials', async () => {
    const hashedPassword = await bcrypt.hash('correctpassword', 10);
    const mockUser = {
      id: 1,
      email: 'test@example.com',
      password: hashedPassword,
      full_name: 'Test User',
      is_active: 1,
    };
    const mockDb = {
      execute: jest.fn()
        .mockResolvedValueOnce([[mockUser]]) // First call: find user
        .mockResolvedValueOnce([{ affectedRows: 1 }]), // Second call: update last_seen
    };
    getDb.mockResolvedValue(mockDb);

    const { req, res } = createMocks({
      method: 'POST',
      body: {
        email: 'test@example.com',
        password: 'correctpassword',
      },
    });

    await loginHandler(req, res);

    expect(res._getStatusCode()).toBe(200);
    expect(JSON.parse(res._getData())).toEqual({
      success: true,
      message: 'Login berhasil',
      user: {
        id: 1,
        email: 'test@example.com',
        full_name: 'Test User',
      },
    });
    expect(setSession).toHaveBeenCalledWith(res, expect.objectContaining({
      user_id: 1,
      email: 'test@example.com',
    }));
  });
});
