<?php $__engine->extends('layouts/main'); ?>

<?php $__engine->section('content'); ?>

<div style="max-width: 400px; margin: 2rem auto;">
    <h2 style="text-align: center; margin-bottom: 2rem;">Login</h2>

    <?php if (session()->has('error')): ?>
        <div style="background: #3d1f1f; border: 1px solid #ff6b6b; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; color: #ff6b6b;">
            <?= e(session('error')) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login" style="display: flex; flex-direction: column; gap: 1rem;">
        <?= csrf_field() ?>

        <div>
            <label for="email" style="display: block; margin-bottom: 0.25rem; color: #a0a0a0;">E-mail</label>
            <input type="email" id="email" name="email" required
                   style="width: 100%; padding: 0.75rem; background: #1a1a2e; border: 1px solid #333; border-radius: 6px; color: #e0e0e0; font-size: 1rem;">
        </div>

        <div>
            <label for="password" style="display: block; margin-bottom: 0.25rem; color: #a0a0a0;">Senha</label>
            <input type="password" id="password" name="password" required minlength="6"
                   style="width: 100%; padding: 0.75rem; background: #1a1a2e; border: 1px solid #333; border-radius: 6px; color: #e0e0e0; font-size: 1rem;">
        </div>

        <button type="submit"
                style="padding: 0.75rem; background: #00d4ff; color: #0f0f1a; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer;">
            Entrar
        </button>
    </form>

    <p style="text-align: center; margin-top: 1.5rem; color: #666;">
        Nao tem conta? <a href="/register" style="color: #00d4ff; text-decoration: none;">Cadastre-se</a>
    </p>
</div>

<?php $__engine->endSection(); ?>
