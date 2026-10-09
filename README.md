# Laralog Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laralog-client.svg?style=flat-square)](https://packagist.org/packages/laranex/laralog-client)
[![Tests](https://github.com/laranex/laralog-client/actions/workflows/tests.yml/badge.svg)](https://github.com/laranex/laralog-client/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laralog-client.svg?style=flat-square)](https://packagist.org/packages/laranex/laralog-client)
[![License](https://img.shields.io/packagist/l/laranex/laralog-client.svg?style=flat-square)](LICENSE.md)

Laralog for Laravel: a log channel that sends your logs to a Laralog server. Built for humans and AI agents.

## Usage

```bash
composer require laranex/laralog-client
```

```env
LOG_CHANNEL=laralog
LARALOG_CLIENT_BASE_URL=https://laralog.example.com
LARALOG_CLIENT_TEAM_SECRET_KEY=your-team-secret
```

Configuration, channel options, failure handling and testing are covered by the package's agent skill in [`skills/laralog-client/SKILL.md`](skills/laralog-client/SKILL.md) (install it with `npx skills add laranex/laralog-client`, or let Laravel Boost pick it up).
