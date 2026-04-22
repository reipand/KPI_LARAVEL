<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '2.0.0',
    title: 'KPI Laravel API',
    description: 'Multi-tenant HR KPI platform. Covers v1 (existing) and v2 (multi-tenant) endpoints.',
    contact: new OA\Contact(email: 'admin@system.local'),
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Local / Development')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Include: Bearer {token}'
)]
#[OA\Tag(name: 'Auth',            description: 'Login and user session')]
#[OA\Tag(name: 'Tenants',         description: 'Super Admin: tenant lifecycle management')]
#[OA\Tag(name: 'Users',           description: 'User management with Spatie roles')]
#[OA\Tag(name: 'KPI Templates',   description: 'Reusable KPI template + indicator CRUD')]
#[OA\Tag(name: 'KPI Assignments', description: 'Assign templates to employees, submit and review')]
#[OA\Tag(name: 'Tasks',           description: 'Task management v1 and v2')]
#[OA\Tag(name: 'Reports',         description: 'KPI summary and performance reports + PDF export')]
#[OA\Tag(name: 'Audit Logs',      description: 'Immutable activity log viewer')]
#[OA\Tag(name: 'Employees',       description: 'Employee CRUD')]
#[OA\Tag(name: 'Departments',     description: 'Department management')]
#[OA\Tag(name: 'Positions',       description: 'Position management')]
#[OA\Tag(name: 'KPI',             description: 'Employee KPI input, dashboard, and rankings')]
#[OA\Tag(name: 'Notifications',   description: 'In-app notifications')]
final class OpenApi {}
