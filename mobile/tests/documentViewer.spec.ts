import { describe, expect, it } from 'vitest'
import { responseFilename } from '@/native/documentViewer'

describe('document viewer', () => {
  it('uses the authenticated response filename when available', () => {
    expect(
      responseFilename(
        'inline; filename="Laporan Konsultasi.pdf"',
        'dokumen.pdf',
        'application/pdf',
      ),
    ).toBe('Laporan-Konsultasi.pdf')
  })

  it('adds an extension from the content type to a safe fallback', () => {
    expect(responseFilename(undefined, 'Tiket TICKET/01', 'application/pdf'))
      .toBe('Tiket-TICKET01.pdf')
  })
})
