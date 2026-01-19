const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Get payload from Base64 argument
const args = process.argv.slice(2);
const payloadBase64 = args[0];

if (!payloadBase64) {
    console.log(JSON.stringify({ success: false, error: 'No payload provided' }));
    process.exit(1);
}

let data;
try {
    data = JSON.parse(Buffer.from(payloadBase64, 'base64').toString('utf-8'));

    // Log the input data for debugging (Pass visible)
    console.error('INPUT DATA:', JSON.stringify(data, null, 2));

} catch (e) {
    console.log(JSON.stringify({ success: false, error: 'Invalid Base64 Payload' }));
    process.exit(1);
}

// Helper to take screenshot
const takeScreenshot = async (page, name) => {
    try {
        const screenshotPath = path.resolve(__dirname, '../public', `${name}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: true });
    } catch (e) {
        // Ignore screenshot errors
    }
};

const getFirstByXPath = async (context, xpath) => {
    const handle = await context.evaluateHandle((expr) => {
        const result = document.evaluate(
            expr,
            document,
            null,
            XPathResult.FIRST_ORDERED_NODE_TYPE,
            null
        );
        return result.singleNodeValue;
    }, xpath);
    const element = handle.asElement();
    if (!element) {
        await handle.dispose();
        return null;
    }
    return element;
};

const formatDateDMY = (value) => {
    if (!value) return '';
    if (value instanceof Date && !isNaN(value)) {
        const dd = String(value.getDate()).padStart(2, '0');
        const mm = String(value.getMonth() + 1).padStart(2, '0');
        const yyyy = String(value.getFullYear());
        return `${dd}/${mm}/${yyyy}`;
    }
    const str = String(value).trim();
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(str)) return str;
    const isoMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoMatch) return `${isoMatch[3]}/${isoMatch[2]}/${isoMatch[1]}`;
    return str;
};

(async () => {
    let browser = null;
    try {
        browser = await puppeteer.launch({
            headless: false, // Keep visible for user feedback
            defaultViewport: null,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--start-maximized']
        });

        const pages = await browser.pages();
        const page = pages[0]; // Use existing page

        // 1. Login
        // Using the main SOL login which redirects to the menu
        console.error('Navigating to login...');
        await page.goto('https://e-menu.sunat.gob.pe/cl-ti-itmenu/MenuInternet.htm', { waitUntil: 'domcontentloaded' });

        // Handle "Ingresar" button if on landing page
        try {
            const btnIngresar = await page.$x("//a[contains(text(), 'Ingresar')]");
            if (btnIngresar.length > 0) {
                console.error('Clicking Ingresar...');
                await btnIngresar[0].click();
                await page.waitForNavigation({ waitUntil: 'domcontentloaded' });
            }
        } catch (e) { }

        // Check for specific login form type
        console.error('Checking login form...');

        // SKIP clicking DNI button as requested by user
        // try {
        //     await page.waitForSelector('#btnPorDni', { timeout: 5000 });
        //     await page.click('#btnPorDni');
        //     console.error('Clicked DNI tab');
        // } catch (e) {
        //     console.error('DNI tab not found, assuming direct input or different form');
        // }

        // Fill Credentials
        console.error('Filling credentials...');
        await page.waitForSelector('#txtDni, #txtRuc', { timeout: 10000 });

        // Prioritize RUC input since we are skipping DNI tab
        if (await page.$('#txtRuc')) {
            await page.type('#txtRuc', data.login_ruc);
        } else if (await page.$('#txtDni')) {
            await page.type('#txtDni', data.login_ruc || data.login_user);
        }

        await page.type('#txtUsuario', data.login_user);
        await page.type('#txtContrasena', data.login_password);

        const btnSubmit = await page.$('#btnAceptar');
        if (btnSubmit) {
            console.error('Submitting login...');

            // Wait 1 second as requested by user
            await new Promise(r => setTimeout(r, 1000));

            // Wait for navigation OR for an error message
            const navPromise = page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 });
            await btnSubmit.click();
            await new Promise(r => setTimeout(r, 1000));
            // Validate Login Success
            // Check if error appeared
            //try {
            //    await Promise.race([
            //        navPromise,
            //        page.waitForSelector('#divOpcionServicio1', { timeout: 5000 })
            //    ]);
            //} catch (e) {
            //    // Timeout means navigation didn't happen fast or error didn't appear
            //}
            //
            //const errorMsg = await page.$('#divOpcionServicio1');
            //if (errorMsg) {
            //    const text = await page.evaluate(el => el.textContent, errorMsg);
            //    throw new Error('Login Failed: ' + text.trim());
            //}

        } else {
            throw new Error('Login button not found');
        }

        // 2. Navigation through Menu
        console.error('Navigating menu...');
        // Wait for Menu to load
        await page.waitForSelector('#divOpcionServicio1', { timeout: 20000 }); // Generic wait for menu load


        try {
            await page.waitForSelector('#divOpcionServicio1', { timeout: 5000 });
            await page.click('#divOpcionServicio1');
            console.error('Clicked persona tab');
        } catch (e) {
            console.error('personas tab not found');
        }

        await new Promise(r => setTimeout(r, 1000));

        try {
            await page.waitForSelector('#nivel1_11', { timeout: 5000 });
            await page.click('#nivel1_11');
            console.error('Clicked Comprobantes de Pago tab');
        } catch (e) {
            console.error('Comprobantes de Pago tab not found');
        }

        await new Promise(r => setTimeout(r, 1000));

        try {
            await page.waitForSelector('#nivel2_11_5', { timeout: 5000 });
            await page.click('#nivel2_11_5');
            console.error('Clicked SEE - SOL tab');
        } catch (e) {
            console.error('SEE - SOL tab not found');
        }

        await new Promise(r => setTimeout(r, 1000));

        try {
            await page.waitForSelector('#nivel3_11_5_1', { timeout: 5000 });
            await page.click('#nivel3_11_5_1');
            console.error('Clicked Recibo por Honorarios Electrónicos tab');
        } catch (e) {
            console.error('Recibo por Honorarios Electrónicos tab not found');
        }

        await new Promise(r => setTimeout(r, 1000));

        try {
            await page.waitForSelector('#nivel4_11_5_1_1_2', { timeout: 5000 });
            await page.click('#nivel4_11_5_1_1_2');
            console.error('Clicked Emitir Recibo por Honorarios Electrónicos tab');
        } catch (e) {
            console.error('Emitir Recibo por Honorarios Electrónicos tab not found');
        }

        // 3. Handle Emission Frame (Same Page)
        console.error('Waiting for emission form...');





        let emissionPage = page; // Default to main page
        let found = false;
        let clickedWacepta = false;

        await new Promise(r => setTimeout(r, 1000)); // Give it time to load the iframe content

        // DETECT FRAME FIRST
        console.error('Searching for form context (Main or Frame)...');
        // Check main page first
        if (await page.$('input[name="inddeduccion"]') || await page.$('#formaPago1') || await page.$('#destipdoc')) {
            found = true;
            console.error('Found form/input on main page');
        } else {
            // Search frames
            console.error('Searching frames...');
            for (const frame of page.frames()) {
                try {
                    if (await frame.$('input[name="inddeduccion"]') || await frame.$('#formaPago1') || await frame.$('#destipdoc')) {
                        emissionPage = frame;
                        found = true;
                        console.error('Found form/input in frame: ' + frame.name());
                        break;
                    }
                } catch (e) { }
            }
        }

        if (!found) {
            console.error('Warning: Target elements not found in any frame. Screeshotting debug_emission_fail.');
            await takeScreenshot(page, 'debug_emission_fail');
            throw new Error('Emission form (or deduction input) not found in any frame. Check public/debug_emission_fail.png');
        }

        // Deduccion (solo aparece para 3ra categoria)
        if (typeof data.inddeduccion !== 'undefined') {
            console.error('select input inddeduccion...');
            // Try to wait for it on the identified page/frame
            try {
                await emissionPage.waitForSelector('input[name="inddeduccion"]', { timeout: 3000 });
            } catch (e) { }

            const deduccionSi = await emissionPage.$('input[name="inddeduccion"][value="1"]');
            const deduccionNo = await emissionPage.$('input[name="inddeduccion"][value="0"]');

            console.error(
                'deduccion radios found:',
                'si=', Boolean(deduccionSi),
                'no=', Boolean(deduccionNo),
                'value=', data.inddeduccion
            );

            if (deduccionSi || deduccionNo) {
                console.error('click inddeduccion seleccionado...');
                const target = String(data.inddeduccion) === '1' ? deduccionSi : deduccionNo;
                console.error('click...');
                if (target) await target.click();
                const btnWacepta = await emissionPage.$('#wacepta');
                console.error('busca boton continuar...');
                if (btnWacepta) {
                    console.error('click boton continuar...');
                    await btnWacepta.click();
                    clickedWacepta = true;
                    // Wait for navigation/reload within frame
                    await new Promise(r => setTimeout(r, 2000));
                }
            }
        }

        // Wait for iframe or content to load
        await new Promise(r => setTimeout(r, 1000));

        console.error('Found context. Waiting for #formaPago1...');
        await emissionPage.waitForSelector('#formaPago1', { timeout: 15000 });

        // 4. Fill Form

        // Forma de Pago
        console.error('Selecting Forma Pago (Default Contado)...');
        const radioContado = await emissionPage.$('#radioContado') || await emissionPage.$('input[value="CONTADO"]');
        if (radioContado) await radioContado.click();

        // Tipo Doc
        console.error('Selecting Tipo Doc...');
        await emissionPage.select('select[name="tipdoc"], #tipdoc', data.cliente_tipo_doc === 'RUC' ? '6' : '1');

        // Num Doc
        await emissionPage.type('input[name="numdoc"], #numdoc', data.cliente_num_doc);

        // Click Validar
        console.error('Validating RUC/DNI...');
        const btnValidar = await getFirstByXPath(
            emissionPage,
            "//input[@value='Validar RUC o DNI'] | //button[contains(text(), 'Validar')]"
        );
        if (btnValidar) {
            await btnValidar.click();
            // Wait for validation success
            // Check if an alert/error appeared
            await new Promise(r => setTimeout(r, 2000));
        }

        // Continuar
        const btnContinuar = await getFirstByXPath(
            emissionPage,
            "//input[@value='Continuar'] | //button[contains(text(), 'Continuar')]"
        );
        if (btnContinuar) {
            await btnContinuar.click();
            await emissionPage.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => { });
        }

        // 5. Description
        await emissionPage.waitForSelector('input[name="motivo"]', { timeout: 10000 });
        await emissionPage.type('input[name="motivo"]', data.descripcion);

        if (data.observacion) {
            await emissionPage.type('input[name="observacion"]', data.observacion);
        }

        // Date - Force value via js if simple type doesn't work
        await emissionPage.evaluate((date) => {
            const input = document.querySelector('input[name="fecemi"]');
            if (input) input.value = date;
        }, formatDateDMY(data.fecha_emision));

        // Rent Type
        if (data.tipo_renta === 'A') {
            const rA = await emissionPage.$('input[name="indrenta1"][value="A"]');
            if (rA) await rA.click();
        } else {
            const rB = await emissionPage.$('input[name="indrenta1"][value="B"]');
            if (rB) await rB.click();
        }

        // Retention
        if (data.retencion === 'SI') {
            const rY = await emissionPage.$('#medioPagoR1');
            if (rY) await rY.click();
        } else {
            const rN = await emissionPage.$('#medioPagoR2');
            if (rN) await rN.click();
        }

        // Medio Pago
        await emissionPage.select('select[name="mediopago"]', data.medio_pago);

        // Currency
        await emissionPage.select('select[name="moneda"]', data.moneda);

        // Amount
        console.error('Entering Amount...');
        const amountInput = await emissionPage.$('#totalHonorarios');
        if (amountInput) {
            await amountInput.click({ clickCount: 3 });
            await amountInput.press('Backspace');
            await amountInput.type(String(data.monto_total));
        }

        console.error('Form filled.');

        console.log(JSON.stringify({ success: true, message: 'Formulario llenado correctamente' }));

        // 6. Preview and Emit
        console.error('Clicking Continuar (wacepta)...');
        // Click "Continuar" to go to preview

        await emissionPage.waitForSelector('input[name="wacepta"][value="Continuar"]', { timeout: 10000 });
        await emissionPage.click('input[name="wacepta"][value="Continuar"]');


        await new Promise(r => setTimeout(r, 500)); // Wait 0.5s as requested

        console.error('Clicking Emitir Recibo...');
        // Look for input with value="Emitir Recibo" and name="wacepta"
        const btnEmitir = await getFirstByXPath(
            emissionPage,
            "//input[@value='Emitir Recibo'][@name='wacepta']"
        );

        if (btnEmitir) {
            // CAUTION: This actually emits the receipt. Use with care.
            await btnEmitir.click();
            console.error('Receipt Emitted!');

            // Wait before filling email form
            await new Promise(r => setTimeout(r, 2000));

            // Fill email and submit
            let emailPage = emissionPage;
            let emailFound = false;

            try {
                if (await emissionPage.$('input[name="email"]')) {
                    emailFound = true;
                } else if (await page.$('input[name="email"]')) {
                    emailPage = page;
                    emailFound = true;
                } else {
                    for (const frame of page.frames()) {
                        try {
                            if (await frame.$('input[name="email"]')) {
                                emailPage = frame;
                                emailFound = true;
                                break;
                            }
                        } catch (e) { }
                    }
                }
            } catch (e) { }

            if (emailFound) {
                await emailPage.waitForSelector('input[name="email"]', { timeout: 10000 });
                const emailInput = await emailPage.$('input[name="email"]');
                if (emailInput) {
                    await emailInput.click({ clickCount: 3 });
                    await emailInput.press('Backspace');
                    await emailInput.type(String(data.email || ''));
                }

                const btnEnviar = await emailPage.$('input.form-button[value="Enviar"]')
                    || await emailPage.$('input[type="submit"][value="Enviar"]');
                if (btnEnviar) {
                    await btnEnviar.click();
                    console.error('Email sent.');
                } else {
                    console.error('Enviar button not found.');
                }
            } else {
                console.error('Email input not found after emission.');
            }

            // Wait for result (success message or PDF link)
            await new Promise(r => setTimeout(r, 5000));
        } else {
            console.error('Emitir Recibo button not found');
        }

    } catch (error) {
        console.error('Error: ' + error.message);
        if (browser) {
            const pages = await browser.pages();
            if (pages.length > 0) await takeScreenshot(pages[pages.length - 1], 'error_final');
        }
        console.log(JSON.stringify({ success: false, error: error.message }));
    } finally {
        // Keep browser open for a bit for user to see
        await new Promise(r => setTimeout(r, 5000));
        if (browser) await browser.close();
    }
})();
