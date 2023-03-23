# ownCloud Development Environment

## Installation / Running

```bash
docker-compose up
```

Afterwards you should be able to open <http://localhost:8081> (admin/admin) to
login to your ownCloud instance.

## Tip

In case something is broken try to reset the container:

```bash
docker-compose build; docker-compose down; docker volume prune -f
```
