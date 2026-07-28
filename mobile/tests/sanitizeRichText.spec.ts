import { describe, expect, it } from 'vitest'
import { sanitizeRichText } from '@/security/sanitizeRichText'

describe('sanitizeRichText', () => {
  it('keeps formatting but strips executable markup and attributes', () => {
    const clean = sanitizeRichText(
      '<p onclick="alert(1)">Safe <strong style="color:red">text</strong></p>' +
        '<img src=x onerror="alert(2)"><script>alert(3)</script><svg onload="alert(4)"/>',
    )

    expect(clean).toBe('<p>Safe <strong>text</strong></p>')
    expect(clean).not.toContain('onerror')
    expect(clean).not.toContain('onclick')
    expect(clean).not.toContain('script')
    expect(clean).not.toContain('svg')
  })

  it('preserves table structure while stripping its attributes', () => {
    expect(
      sanitizeRichText(
        '<table onclick="x"><tbody><tr><td style="color:red">Kolom</td></tr></tbody></table>',
      ),
    ).toBe('<table><tbody><tr><td>Kolom</td></tr></tbody></table>')
  })

  it('preserves the ordered and unordered lists offered by the mobile toolbar', () => {
    expect(
      sanitizeRichText('<ol><li>Satu</li></ol><ul><li>Dua</li></ul>'),
    ).toBe('<ol><li>Satu</li></ol><ul><li>Dua</li></ul>')
  })
})
