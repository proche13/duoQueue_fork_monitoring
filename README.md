DuoQueue — Monitoring & Observability Stack

A fork of DuoQueue, a PHP/MySQL gaming matchmaking platform, instrumented with a production-style observability stack using Prometheus, Grafana, Pushgateway, and Alertmanager, all containerised with Docker Compose.

Stack

The application runs across six Docker containers: the DuoQueue PHP app served by Apache, a MySQL 8.0 database, Prometheus for metrics collection, Pushgateway to receive metrics pushed by the PHP app, Alertmanager for alert routing, and Grafana for visualisation.

Custom Metrics

A PHP metrics helper class pushes the following custom metrics from the app to Pushgateway: matchmaking queue depth, active sessions, total likes given, total matches made, and total messages sent.

Alert Rules

Four alert rules are configured in Prometheus: high queue depth, high endpoint latency, no active sessions, and no matches being made. Alerts are routed through Alertmanager.

Getting Started

Clone the repo, copy .env.example to .env and fill in your database credentials, then run docker compose up -d. The app will be available at localhost:8080, Grafana at localhost:3000, Prometheus at localhost:9090, Pushgateway at localhost:9091, and Alertmanager at localhost:9093. The DuoQueue Grafana dashboard loads automatically via provisioning.

Project Structure

The repo contains a Dockerfile and docker-compose.yml at the root. Database connection and metrics helper are in config/. All monitoring configuration is in monitoring/, split into prometheus/, alertmanager/, and grafana/provisioning/. The PHP application files are in webpages/.