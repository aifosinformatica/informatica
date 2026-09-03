<fieldset class="equipment-fields">
    <legend>Datos del equipo (opcional)</legend>
    <p>Completá lo que sepas. También podés adjuntar hasta 5 fotos.</p>
    <?php if (!empty($equipmentOptions)): ?>
        <label>Equipo ya registrado
            <select name="existing_equipment_id">
                <option value="">Registrar un equipo nuevo</option>
                <?php foreach ($equipmentOptions as $equipmentOption): ?>
                    <option value="<?= (int) $equipmentOption['id'] ?>"><?= e(equipment_option_label($equipmentOption)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p>Si elegís uno existente, se asociará al nuevo ingreso y no se duplicará su ficha.</p>
    <?php endif; ?>
    <div class="form-row form-row--2">
        <label>Tipo de equipo
            <select name="equipment_type">
                <option value="">Sin especificar</option>
                <option value="notebook">Notebook</option>
                <option value="escritorio">PC de escritorio</option>
                <option value="otro">Otro</option>
            </select>
        </label>
        <label>Sistema operativo
            <select name="operating_system">
                <option value="">Sin especificar</option>
                <option value="windows">Windows</option>
                <option value="macos">macOS</option>
                <option value="linux">Linux</option>
                <option value="otro">Otro</option>
                <option value="no_sabe">No lo sé</option>
            </select>
        </label>
    </div>
    <div class="form-row form-row--2">
        <label>Tipo de disco
            <select name="disk_type">
                <option value="">Sin especificar</option>
                <option value="hdd">HDD</option>
                <option value="ssd_sata">SSD SATA</option>
                <option value="ssd_nvme">SSD NVMe</option>
                <option value="otro">Otro</option>
                <option value="no_sabe">No lo sé</option>
            </select>
        </label>
        <label>Tipo de RAM <input type="text" name="ram_type" maxlength="40" placeholder="Ej: DDR4"></label>
    </div>
    <div class="form-row form-row--2">
        <label>Cantidad de RAM <input type="text" name="ram_amount" maxlength="40" placeholder="Ej: 16 GB"></label>
        <label>CPU <input type="text" name="cpu" maxlength="160" placeholder="Ej: Intel Core i5-10400"></label>
    </div>
    <div class="form-row form-row--2">
        <label>Marca <input type="text" name="brand" maxlength="100" placeholder="Ej: Lenovo"></label>
        <label>Modelo <input type="text" name="model" maxlength="160" placeholder="Ej: ThinkPad E14"></label>
    </div>
    <label>Fotos del equipo
        <input type="file" name="equipment_photos[]" accept="image/jpeg,image/png,image/webp" multiple>
        <small>JPG, PNG o WebP. Máximo 5 fotos de 8 MB cada una.</small>
    </label>
</fieldset>
