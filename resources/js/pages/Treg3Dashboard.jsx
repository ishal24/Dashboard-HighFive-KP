import '../../../public/css/inertia.css'
import React from 'react'

import TrendRevenueChart from '@/components/trend-revenue-chart'
import { RevenueOverview } from '@/components/revenue-overview'
import { WitelPerformance } from '@/components/witel-performance'
import { DivisionOverview } from '@/components/division-overview'
import { TopCustomers } from '@/components/top-customers'
import { CCPerformance } from '@/components/cc-performance'


export default function Treg3Dashboard() {
  return (
    <main className="flex-1 bg-gray-50">
      {/* page container */}
      <div className="mx-auto px-16 py-20 space-y-8">
        <div className="section-block">
          <div className="header-dashboard">
            <div className="header-content">
              <div className="header-text">
                <h1 className="header-title">Dashboard Performansi CC &amp; Witel</h1>
                <p className="header-subtitle">
                  Monitoring dan Analisis Performa Revenue CC dan Witel
                </p>
              </div>
              <div className="header-actions">
                <button className="export-btn">
                  <i className="fas fa-download"></i> Export Data
                </button>
              </div>
            </div>
          </div>
        </div>

        <section id="trend-revenue" className="section-block">
          <TrendRevenueChart initialYear={2025} />
        </section>

        <section id="revenue-performance" className="section-block">
          <RevenueOverview />
        </section>

        <section id="witel-performance" className="section-block">
          <WitelPerformance />
        </section>

        <section id="top-customers" className="h-full section-block">
          <TopCustomers />
        </section>
        
        <section id="division-overview" className="h-full section-block">
          <DivisionOverview />
        </section>

        <section id="cc-performance" className="section-block">
          <CCPerformance />
        </section>
      </div>
    </main>
  )
}